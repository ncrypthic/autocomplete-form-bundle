<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Ris\AutocompleteFormBundle\Form\Type;

use Doctrine\ORM\QueryBuilder;

/**
 * Description of AutocompleteType
 *
 * @author Lim Afriyadi <lim.afriyadi@rajawalisoftware.com>
 */
class AutocompleteType extends AbstractAutocompleteType
{
    public function buildView(\Symfony\Component\Form\FormView $view, \Symfony\Component\Form\FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['widget'] = $options['widget'];
    }
    /**
     * {@inheritdoc}
     */
    public function onSetData($data, QueryBuilder $queryBuilder)
    {
        
    }

    /**
     * {@inheritdoc}
     */
    public function onSubmit($data, QueryBuilder $queryBuilder)
    {
        
    }
}