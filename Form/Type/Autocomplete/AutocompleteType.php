<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LLA\AutocompleteFormBundle\Form\Type\Autocomplete;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

/**
 * Autocomplete entity type
 *
 * @author Lim Afriyadi <lim.afriyadi@rajawalisoftware.com>
 */
class AutocompleteType extends AbstractAutocompleteType
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['widget'] = $options['widget'];
    }
}