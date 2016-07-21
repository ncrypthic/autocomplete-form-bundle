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
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
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
     * @var Loader\ORMQueryBuilderLoader
     */
    private $loader;
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;
    /**
     * @var \Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface
     */
    private $choiceListFactory;
    /**
     * @var \Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface
     */
    private $choiceLoader;

    public function __construct(ManagerRegistry $registry, PropertyAccessorInterface $propertyAccessor = null, ChoiceListFactoryInterface $choiceListFactory = null)
    {
        parent::__construct($registry);
        $this->registry = $registry;
        $this->choiceListFactory = $choiceListFactory ?: new PropertyAccessDecorator(
            new DefaultChoiceListFactory(), $propertyAccessor
        );
        $this->loader       = new Loader\ORMQueryBuilderLoader(null);
        $this->choiceLoader = new AutocompleteDoctrineChoiceLoader($this->choiceListFactory);
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if(is_callable($options['query_builder'])) {
            $manager    = $this->registry->getManager('default');
            $repository = $manager->getRepository($options['class']);
            $this->queryBuilder = call_user_func_array(
                    $options['query_builder'], array($repository));
        }  else {
            $this->queryBuilder = $options['query_builder'];
        }
        if($this->queryBuilder instanceof QueryBuilder == false) {
            throw new InvalidConfigurationException(
                '`query_builder` must be callable or an instance of Doctrine\ORM\QueryBuilder');
        }
        // Set max results
        $this->queryBuilder->setMaxResults($options['max_results']);
        $this->loader->setQueryBuilder($this->queryBuilder);
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
    
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if($form->getData() === null) {
            $loader = clone $this->loader;
            $loader->setQueryBuilder($this->queryBuilder);
            $view->vars['choices'] = $this->choiceLoader->setEntityLoader($loader)->loadChoiceList();
        }
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
                $this->choiceLoader
                        ->setObjectManager($options['em'])
                        ->setClass($options['class'])
                        ->setIdReader($idReader)
                        ->setEntityLoader($this->loader);
                
                return $this->choiceLoader;
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
        return $this->loader;
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
        $qb      = $e->getForm()->getConfig()->getOption("query_builder");
        $evt     = new AutocompleteFormEvent($qb, $e);
        $handler = $e->getForm()->getConfig()->getOption('set_handler');
        if(is_callable($handler)) {
            $qb   = call_user_func_array($handler, array($evt));
        }
        if($qb instanceof QueryBuilder) {
            $loader = clone $this->loader;
            $loader->setQueryBuilder($qb);
            $this->choiceLoader->setEntityLoader($loader);
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
        $qb      = $e->getForm()->getConfig()->getOption("query_builder");
        $evt     = new AutocompleteFormEvent($qb, $e);
        $handler = $e->getForm()->getConfig()->getOption('submit_handler');
        if(is_callable($handler)) {
            $qb  = call_user_func_array($handler, array($evt));
        }
        if($qb instanceof QueryBuilder) {
            $this->loader->setQueryBuilder($qb);
            $this->choiceLoader->setEntityLoader($this->loader);
        }
    }
}