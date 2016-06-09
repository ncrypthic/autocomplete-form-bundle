<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LLA\AutocompleteFormBundle\Form\Type\Autocomplete\Loader;

use Symfony\Bridge\Doctrine\Form\ChoiceList\DoctrineChoiceLoader;

/**
 * Description of AutocompleteChoiceLoader
 *
 * @author Lim Afriyadi <lim.afriyadi@rajawalisoftware.com>
 */
class AutocompleteDoctrineChoiceLoader extends DoctrineChoiceLoader
{
    public function loadChoiceList($value = null)
    {
        $objects = $this->objectLoader
            ? $this->objectLoader->getEntities()
            : $this->manager->getRepository($this->class)->findAll();

        $this->choiceList = $this->factory->createListFromChoices($objects, $value);

        return $this->choiceList;
    }
}