<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LLA\AutocompleteFormBundle\Form\Type\Autocomplete;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use LLA\AutocompleteFormBundle\Event\AutocompleteFormEvent;
use LLA\AutocompleteFormBundle\Exception\InvalidQueryBuilderException;

/**
 * Base class form Autocomplete form type
 *
 * @author Lim Afriyadi <lim.afriyadi@rajawalisoftware.com>
 */
abstract class AbstractAutocompleteType extends AbstractType
{
    /**
     * @var Loader\ORMQueryBuilderLoader
     */
    private $loader;
    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->loader   = new Loader\ORMQueryBuilderLoader(null);
        $this->registry = $registry;
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if(is_callable($options['query_builder'])) {
            $manager    = $this->registry->getManager();
            $repository = $manager->getRepository($options['class']);
            $qb = call_user_func($options['query_builder'], array($repository));
        } else {
            $qb = $options['query_builder'];
        } 
        if($qb instanceof QueryBuilder == false) {
            throw new InvalidConfigurationException(
                '`query_builder` must be callable or an instance of Doctrine\ORM\QueryBuilder');
        }
        // Set max results
        $qb->setMaxResults($options['max_results']);
        $this->loader->setQueryBuilder($qb);
        if(is_object($options['model_transformer'])) {
            $builder->addModelTransformer($options['model_transformer']);
        }
        if(is_object($options['view_transformer'])) {
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
        $resolver->setDefaults(array(
            'model_transformer' => null,
            'view_transformer'  => null,
            'data_property'     => "",
            'max_results'       => 10,
            'query_builder'     => null,
            'widget'            => 'choice',
            'set_handler'       => function(AutocompleteFormEvent $e){},
            'submit_handler'    => function(AutocompleteFormEvent $e){},
        ));
        $resolver->setRequired('data_property');
        $resolver->setRequired('query_builder');
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
    public final function getLoader()
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
        $prev    = $this->loader->getQueryBuilder();
        $evt     = new AutocompleteFormEvent($prev, $e);
        $handler = $e->getForm()->getConfig()->getOption('set_handler');
        $qb      = null;
        if(is_callable($handler) && $e->getData()) {
            $qb   = call_user_func_array($handler, array($evt));
        }
        if($qb instanceof QueryBuilder) {
            $this->loader->setQueryBuilder($qb);
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
        $prev    = $this->loader->getQueryBuilder();
        $evt     = new AutocompleteFormEvent($prev, $e);
        $handler = $e->getForm()->getConfig()->getOption('submit_handler');
        $qb      = null;
        if(is_callable($handler) && $e->getData()) {
            $qb   = call_user_func_array($handler, array($evt));
        }
        if($qb instanceof QueryBuilder) {
            $this->loader->setQueryBuilder($qb);
        }
    }
}