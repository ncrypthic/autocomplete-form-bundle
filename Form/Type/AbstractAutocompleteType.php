<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Ris\AutocompleteFormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;
use Ris\AutocompleteFormBundle\Exception\InvalidQueryBuilderException;

/**
 * Base class form Autocomplete form type
 *
 * @author Lim Afriyadi <lim.afriyadi@rajawalisoftware.com>
 */
abstract class AbstractAutocompleteType extends AbstractType
{
    /**
     * @var Entity\Loader\ORMQueryBuilderLoader
     */
    private $loader;
    /**
     * @var Registry
     */
    private $registry;

    public function __construct(Registry $register)
    {
        $this->loader   = new Entity\Loader\ORMQueryBuilderLoader(null);
        $this->registry = $register;
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
    
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        
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
            'loader'            => $this->loader,
            'model_transformer' => null,
            'view_transformer'  => null,
            'data_property'     => "",
            'max_results'       => 10,
            'query_builder'     => null,
            'widget'            => 'choice'
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
            return 'text';
        }
        return 'entity';
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
        return 'autocomplete';
    }
    
    /**
     * FormEvents::PRE_SET_DATA handler to allow modification of `query_builder`
     * to generate new choice list based on set data
     * 
     * @param FormEvent $evt
     * @throws InvalidQueryBuilderException
     */
    public final function preSetDataHandler(FormEvent $evt)
    {
        $prev = $this->loader->getQueryBuilder();
        $qb   = $this->onSetData($evt->getData(), $prev);
        if($qb instanceof QueryBuilder) {
            $this->loader->setQueryBuilder($qb);
        }
    }
    
    /**
     * FormEvents::PRE_SET_DATA handler to allow modification of `query_builder`
     * to generate new choice list based on submitted data
     * 
     * @param FormEvent $evt
     * @throws InvalidQueryBuilderException
     */
    public final function preSubmitHandler(FormEvent $evt)
    {
        $prev = $this->loader->getQueryBuilder();
        $qb   = $this->onSubmit($evt->getData(), $prev);
        if($qb instanceof QueryBuilder) {
            $this->loader->setQueryBuilder($qb);
        }
    }
    
    /**
     * @param string $data
     * @param QueryBuilder $queryBuilder Previously query builder
     * @return \Doctrine\ORM\QueryBuilder
     */
    abstract public function onSetData($data, QueryBuilder $queryBuilder);
    
    /**
     * @param string $data
     * @param QueryBuilder $queryBuilder Previously query builder
     * @return \Doctrine\ORM\QueryBuilder
     */
    abstract public function onSubmit($data, QueryBuilder $queryBuilder);
}