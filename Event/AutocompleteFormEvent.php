<?php

namespace Ris\AutocompleteFormBundle\Event;

use Symfony\Component\Form\FormEvent;
use Doctrine\ORM\QueryBuilder;

/**
 * Autocomplete form event
 */
class AutocompleteFormEvent
{
    /**
     * @var FormEvent
     */
    private $formEvent;
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;
    
    public function __construct(QueryBuilder $queryBuilder, FormEvent $evt)
    {
        $this->formEvent = $evt;
        $this->queryBuilder = $queryBuilder;
    }
    
    /**
     * Get original form event
     * 
     * @return FormEvent
     */
    public function getFormEvent()
    {
        return $this->formEvent;
    }
    
    /**
     * Get form's existing query builder
     * 
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }
}