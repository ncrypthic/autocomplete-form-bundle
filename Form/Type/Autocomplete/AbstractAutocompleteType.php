<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LLA\AutocompleteFormBundle\Form\Type\Autocomplete;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;
use Symfony\Component\Form\ChoiceList\Factory\PropertyAccessDecorator;
use Symfony\Component\Form\ChoiceList\Factory\DefaultChoiceListFactory;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\OptionsResolver\Options;
use LLA\AutocompleteFormBundle\Event\AutocompleteFormEvent;
use LLA\AutocompleteFormBundle\Exception\InvalidQueryBuilderException;
use LLA\AutocompleteFormBundle\Form\Type\Autocomplete\Loader\AutocompleteDoctrineChoiceLoader;

/**
 * Base class form Autocomplete form type
 *
 * @author Lim Afriyadi <lim.afriyadi@rajawalisoftware.com>
 */
abstract class AbstractAutocompleteType extends EntityType
{
    /**
     * @var ChoiceListFactoryInterface
     */
    private $choiceListFactory;

    public function __construct(ManagerRegistry $registry, PropertyAccessorInterface $propertyAccessor = null, ChoiceListFactoryInterface $choiceListFactory = null)
    {
        $this->choiceListFactory = $choiceListFactory ?: new PropertyAccessDecorator(
            new DefaultChoiceListFactory(), $propertyAccessor
        );
        parent::__construct($registry, null, $this->choiceListFactory);
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if(is_callable($options['query_builder'])) {
            $manager    = $this->registry->getManager('default');
            $repository = $manager->getRepository($options['class']);
            $queryBuilder = call_user_func_array(
                    $options['query_builder'], array($repository));
        }  else {
            $queryBuilder = $options['query_builder'];
        }
        if($queryBuilder instanceof QueryBuilder == false) {
            throw new InvalidConfigurationException(
                '`query_builder` must be callable or an instance of Doctrine\ORM\QueryBuilder');
        }
        // Set max results
        $queryBuilder->setMaxResults($options['max_results']);
        $choiceLoader = $options['choice_loader'];
        if($choiceLoader instanceof AutocompleteDoctrineChoiceLoader) {
            $choiceLoader->getObjectLoader()->setQueryBuilder($queryBuilder);
        }
        if(isset($options['model_transformer']) && is_object($options['model_transformer'])) {
            $builder->addModelTransformer($options['model_transformer']);
        }
        if(isset($options['model_transformer']) && is_object($options['view_transformer'])) {
            $builder->addViewTransformer($options['view_transformer']);
        }
        $builder->addEventListener(FormEvents::PRE_SET_DATA,
                array($this, 'preSetDataHandler'));
        $builder->addEventListener(FormEvents::PRE_SUBMIT, 
                array($this, 'preSubmitHandler'));
    }
    
    /**
     * Maintain BC
     * 
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(array(
            'choice_loader' => function (Options $options) {
                $idReader = $options['id_reader'] 
                        ?: new IdReader($options['em'], $options['class']);
                $loader = new Loader\ORMQueryBuilderLoader($options['query_builder'], null, $options['class']);
                $choiceLoader = new AutocompleteDoctrineChoiceLoader($this->choiceListFactory);
                $choiceLoader->setObjectManager($options['em'])
                    ->setClass($options['class'])
                    ->setIdReader($idReader)
                    ->setEntityLoader($loader);
                
                return $choiceLoader;
            },
            'multiple' => false,
            'model_transformer' => null,
            'view_transformer' => null,
            'max_results' => 10,
            'widget' => 'choice',
            'set_handler' => function(AutocompleteFormEvent $e){},
            'submit_handler' => function(AutocompleteFormEvent $e){}
        ));
    }
    
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        // BC for Symfony < 3
        if (!method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')) {
            return 'entity';
        }
        
        return 'Symfony\Bridge\Doctrine\Form\Type\EntityType';
    }
    
    /**
     * Get entity choice loader
     * 
     * @return Entity\Loader\ORMQueryBuilderLoader
     */
    public function getLoader(ObjectManager $manager, $queryBuilder, $class)
    {
        return new Loader\ORMQueryBuilderLoader($queryBuilder);
    }
    
    /**
     * Get form name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }
    
    /**
     * Get twig block prefix
     * 
     * @return string
     * @codeCoverageIgnore
     */
    public function getBlockPrefix()
    {
        return 'autocomplete';
    }
    
    /**
     * FormEvents::PRE_SET_DATA handler to allow modification of `query_builder`
     * to generate new choice list based on set data
     * 
     * @param FormEvent $e
     * @throws InvalidQueryBuilderException
     */
    public final function preSetDataHandler(FormEvent $e)
    {
        $config  = $e->getForm()->getConfig();
        $qb      = clone $config->getOption('query_builder');
        $class   = $config->getOption("class");
        $label   = $config->getOption("choice_label");
        $evt     = new AutocompleteFormEvent($qb, $e);
        $handler = $config->getOption('set_handler');
        $choiceLoader = $config->getOption('choice_loader');
        if($choiceLoader instanceof AutocompleteDoctrineChoiceLoader) {
            if(is_callable($handler)) {
                $qb  = call_user_func_array($handler, array($evt));
            }
            if($qb instanceof QueryBuilder) {
                $qb->setMaxResults($config->getOption('max_results'));
                $choiceLoader->getObjectLoader()->setQueryBuilder($qb);
                $choiceLoader->setClass($config->getOption('class'));
            }
        }
    }
    
    /**
     * FormEvents::PRE_SET_DATA handler to allow modification of `query_builder`
     * to generate new choice list based on submitted data
     * 
     * @param FormEvent $e
     * @throws InvalidQueryBuilderException
     */
    public final function preSubmitHandler(FormEvent $e)
    {
        $config  = $e->getForm()->getConfig();
        $qb      = clone $config->getOption('query_builder');
        $class   = $config->getOption("class");
        $label   = $config->getOption("choice_label");
        $evt     = new AutocompleteFormEvent($qb, $e);
        $handler = $config->getOption('submit_handler');
        $choiceLoader = $config->getOption('choice_loader');
        if($choiceLoader instanceof AutocompleteDoctrineChoiceLoader) {
            if(is_callable($handler)) {
                $qb  = call_user_func_array($handler, array($evt));
            }
            if($qb instanceof QueryBuilder) {
                $qb->setMaxResults($config->getOption('max_results'));
                $choiceLoader->getObjectLoader()->setQueryBuilder($qb);
                $choiceLoader->setClass($config->getOption('class'));
            }
        }
    }
}