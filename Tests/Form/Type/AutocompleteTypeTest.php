<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Ris\AutocompleteFormBundle\Tests\Form\Type;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\PreloadedExtension;
use Ris\AutocompleteFormBundle\Test\Helper;
use Ris\AutocompleteFormBundle\Form\Type\Autocomplete\AutocompleteType;
use Ris\AutocompleteFormBundle\Tests\Entity\DummyEntity;
use Symfony\Component\Form\FormEvents;
use Ris\AutocompleteFormBundle\Event\AutocompleteFormEvent;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;

/**
 * Description of AutocompleteTypeTest
 *
 * @author Lim Afriyadi <lim.afriyadi@rajawalisoftware.com>
 */
class AutocompleteTypeTest extends TypeTestCase
{
    const MAX_RESULTS = 5;
    /**
     * @var \Doctrine\Bundle\DoctrineBundle\Registry
     */
    private $registry;
    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    private $em;
    
    protected function setUp()
    {
        $this->em = Helper::createTestEntityManager();
        $this->registry = $this->createRegistryMock($this->em);
        
        parent::setUp();
        
        $schemaTool = new SchemaTool($this->em);
        $entityClasses = array($this->em->getClassMetadata(DummyEntity::class));
        try {
            $schemaTool->dropSchema($entityClasses);
        } catch (\Exception $e) {
            echo 'Failed to drop schema';
        }

        try {
            $schemaTool->createSchema($entityClasses);
        } catch (\Exception $e) {
            echo 'Failed to create schema';
        }
        for($i = 1; $i < 11; $i++) {
            $dummy = new DummyEntity();
            $dummy->setId(1);
            $dummy->setName("test ${i}");
            $this->em->persist($dummy);
        }
        $this->em->flush();
    }
    
    public function testDefaultChoiceList()
    {
        $repo    = $this->em->getRepository(DummyEntity::class);
        $results = $repo->findBy(array(), null, self::MAX_RESULTS);
        $view    = $this->createAutocompleteTypeForm()->createView();
        $choices = $view->vars['choices'];
        $expected = array();
        foreach($results as $result) {
            $choice = new ChoiceView($result, $result->getId(), $result->getId());
            $expected[$result->getId()] = $choice;
        }
        $this->assertEquals($expected, $choices);
        $this->assertEquals(self::MAX_RESULTS, count($choices));
    }
    
    public function testSetDataOutOfDefaultList()
    {
        $data     = 10;
        $expected = $this->em->getRepository(DummyEntity::class)->find($data);
        $form     = $this->createAutocompleteTypeForm();
        $form->setData($expected);
        $view     = $form->createView();
        $this->assertEquals(1, count($view->vars['choices']));
        $choiceView = array_pop($view->vars['choices']);
        $this->assertEquals($data, $choiceView->data->getId());
    }
    
    public function testSubmitDataOutOfDefaultList()
    {
        $expected = new DummyEntity();
        $expected->setId(10)->setName('test 10');
        
        $submittedData = 10;
        $form = $this->createAutocompleteTypeForm();
        $form->submit($submittedData);
        
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $form->getData());
    }
    
    private function createAutocompleteTypeForm($maxResults = self::MAX_RESULTS)
    {
        $options = array(
            'class'          => DummyEntity::class,
            'choice_label'   => function(DummyEntity $test) {
                return $test->getId();
            },
            'max_results'    => $maxResults,
            'query_builder'  => function(EntityRepository $repo) {
                return $repo->createQueryBuilder('q');
            },
            'set_handler'    => function(AutocompleteFormEvent $e) {
                return $e->getQueryBuilder()->where('q.id = :id')
                        ->setParameter('id', $e->getFormEvent()->getData());
            },
            'submit_handler' => function(AutocompleteFormEvent $e) {
                return $e->getQueryBuilder()->where('q.id = :id')
                        ->setParameter('id', $e->getFormEvent()->getData());
            }
        );
        
        return $this->factory->create(AutocompleteType::class, null, $options);
    }
    
    protected function createRegistryMock($em)
    {
        $registry = $this->getMockBuilder('Doctrine\Common\Persistence\AbstractManagerRegistry')
                ->disableOriginalConstructor()
                ->getMock();
        $registry->method('getManager')
            ->with($this->any())
            ->will($this->returnValue($em));
        $registry->method('getManagerForClass')
            ->will($this->returnValue($em));

        return $registry;
    }
    
    protected function getExtensions()
    {
        $autocompleteType = new AutocompleteType($this->registry);

        return array_merge(parent::getExtensions(), array(
            new DoctrineOrmExtension($this->registry),
            new PreloadedExtension(array($autocompleteType, $this->registry), array())
        ));
    }
}