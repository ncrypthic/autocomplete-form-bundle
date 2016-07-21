<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LLA\AutocompleteFormBundle\Form\Type\Autocomplete\Loader;

use Symfony\Bridge\Doctrine\Form\ChoiceList\DoctrineChoiceLoader;
use Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bridge\Doctrine\Form\ChoiceList\IdReader;
use Symfony\Bridge\Doctrine\Form\ChoiceList\EntityLoaderInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

/**
 * Description of AutocompleteChoiceLoader
 *
 * @author Lim Afriyadi <lim.afriyadi@rajawalisoftware.com>
 */
class AutocompleteDoctrineChoiceLoader implements ChoiceLoaderInterface
{
    private $class;
    private $objectLoader;
    private $manager;
    private $factory;
    private $idReader;
    
    public function __construct(ChoiceListFactoryInterface $factory, ObjectManager $manager = null, $class = null, IdReader $idReader = null, EntityLoaderInterface &$objectLoader = null)
    {
        $this->class = $class;
        $this->manager = $manager;
        $this->objectLoader = $objectLoader;
        $this->factory = $factory;
        $this->idReader = $idReader;
    }
    
    public function setObjectManager(ObjectManager $manager)
    {
        $this->manager = $manager;
        
        return $this;
    }
    
    public function setClass($class)
    {
        $this->class = $class;
        return $this;
    }
    
    public function setEntityLoader(EntityLoaderInterface $objectLoader)
    {
        $this->objectLoader = $objectLoader;
        return $this;
    }
    
    public function setIdReader(IdReader $idReader)
    {
        $this->idReader = $idReader;
        return $this;
    }
    
    public function loadChoiceList($value = null)
    {
        $objects = $this->objectLoader->getEntities();
        return $this->factory->createListFromChoices($objects, $value);
    }
    
    /**
     * {@inheritdoc}
     */
    public function loadValuesForChoices(array $choices, $value = null)
    {
        // Performance optimization
        if (empty($choices)) {
            return array();
        }

        // Optimize performance for single-field identifiers. We already
        // know that the IDs are used as values

        // Attention: This optimization does not check choices for existence
        if ($this->idReader->isSingleId()) {
            $values = array();

            // Maintain order and indices of the given objects
            foreach ($choices as $i => $object) {
                if ($object instanceof $this->class) {
                    // Make sure to convert to the right format
                    $values[$i] = (string) $this->idReader->getIdValue($object);
                }
            }

            return $values;
        }

        return $this->loadChoiceList($value)->getValuesForChoices($choices);
    }

    /**
     * {@inheritdoc}
     */
    public function loadChoicesForValues(array $values, $value = null)
    {
        // Performance optimization
        // Also prevents the generation of "WHERE id IN ()" queries through the
        // object loader. At least with MySQL and on the development machine
        // this was tested on, no exception was thrown for such invalid
        // statements, consequently no test fails when this code is removed.
        // https://github.com/symfony/symfony/pull/8981#issuecomment-24230557
        if (empty($values)) {
            return array();
        }

        // Optimize performance in case we have an object loader and
        // a single-field identifier
        if (null === $value && $this->objectLoader && $this->idReader->isSingleId()) {
            $unorderedObjects = $this->objectLoader->getEntitiesByIds($this->idReader->getIdField(), $values);
            $objectsById = array();
            $objects = array();

            // Maintain order and indices from the given $values
            // An alternative approach to the following loop is to add the
            // "INDEX BY" clause to the Doctrine query in the loader,
            // but I'm not sure whether that's doable in a generic fashion.
            foreach ($unorderedObjects as $object) {
                $objectsById[(string) $this->idReader->getIdValue($object)] = $object;
            }

            foreach ($values as $i => $id) {
                if (isset($objectsById[$id])) {
                    $objects[$i] = $objectsById[$id];
                }
            }

            return $objects;
        }

        return $this->loadChoiceList($value)->getChoicesForValues($values);
    }
}