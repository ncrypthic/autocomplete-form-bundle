<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace LLA\AutocompleteFormBundle\Tests\Form\Type;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\Test\FormBuilderInterface;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use LLA\AutocompleteFormBundle\Test\Helper;
use LLA\AutocompleteFormBundle\Form\Type\Autocomplete\AutocompleteType;
use LLA\AutocompleteFormBundle\Tests\Entity\DummyEntity;
use LLA\AutocompleteFormBundle\Tests\Entity\ExampleEntity;
use LLA\AutocompleteFormBundle\Event\AutocompleteFormEvent;

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
        $entityClasses = array(
            $this->em->getClassMetadata(DummyEntity::class), 
            $this->em->getClassMetadata(ExampleEntity::class));

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
            $dummy->setId($i);
            $dummy->setName("test ${i}");
            $this->em->persist($dummy);
        }
        for($i; $i < 21; $i++) {
            $example = new ExampleEntity();
            $example->setId($i);
            $example->setAddress("address ${i}");
            $this->em->persist($example);
        }
        $this->em->flush();
    }

    public function testDefaultChoiceList()
    {
        $repo    = $this->em->getRepository(DummyEntity::class);
        $results = $repo->findBy(array(), null, self::MAX_RESULTS);
        $view    = $this->createAutocompleteForm()->createView();
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
        $form     = $this->createAutocompleteForm();
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
        $form = $this->createAutocompleteForm();
        $form->submit($submittedData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $form->getData());
    }

    public function testSetMultipleDataOutOfInitialChoiceList()
    {
        $repo = $this->em->getRepository(DummyEntity::class);
        $data = array($repo->find(1), $repo->find(5));
        $form = $this->createAutocompleteForm(self::MAX_RESULTS, true);
        $form->setData($data);
        $view = $form->createView();
        $choices = array_keys($view->vars['choices']);
        $this->assertEquals(array(1, 5), $choices);
    }

    public function testSubmitMultipleDataOutOfChoiceList()
    {
        $repo = $this->em->getRepository(DummyEntity::class);
        $qb   = $repo->createQueryBuilder('q');
        $result = $qb->select('q')->where($qb->expr()->in('q.id', ':ids'))
                        ->setParameter('ids', array(7, 9))
                        ->getQuery()
                        ->getResult();
        $form = $this->createAutocompleteForm(self::MAX_RESULTS, true);
        $form->submit(array(7, 9));

        $this->assertTrue($form->isSynchronized());
        $expected = new \Doctrine\Common\Collections\ArrayCollection($result);
        $this->assertEquals($expected, $form->getData());
    }

    public function testBuildForm()
    {
        $options = array(
            'multiple'       => false,
            'class'          => DummyEntity::class,
            'choice_label'   => function(DummyEntity $test) {
                return $test->getId();
            },
            'max_results'    => 10,
            'query_builder'  => function(EntityRepository $repo) {
                return $repo->createQueryBuilder('q');
            },
            'set_handler'    => function(AutocompleteFormEvent $e) {
                return $e->getQueryBuilder();
            },
            'submit_handler' => function(AutocompleteFormEvent $e) {
                return $e->getQueryBuilder();
            }
        );
        $builder = $this->createMock('Symfony\Component\Form\Test\FormBuilderInterface');
        /* @var $resolver \Symfony\Component\OptionsResolver\OptionsResolver */
        $resolver = new \Symfony\Component\OptionsResolver\OptionsResolver();
        /* @var $builder FormBuilderInterface */
        $form = new AutocompleteType($this->registry);
        $form->configureOptions($resolver);
        $this->assertEquals($form->buildForm($builder, $resolver->resolve($options)), null);
        $this->assertEquals($form->getName(), 'autocomplete');
    }

    public function testMultipleAutocompleteInSingleForm()
    {
        $dummyOpts = array(
            'multiple'      => false,
            'class'         => DummyEntity::class,
            'choice_label'  => 'id',
            'max_results'   => 10,
            'query_builder' => function(EntityRepository $repo) {
                return $repo->createQueryBuilder('q');
            }
        );
        $exampleOpts = array(
            'multiple'      => false,
            'class'         => ExampleEntity::class,
            'choice_label'  => 'id',
            'max_results'   => 10,
            'query_builder' => function(EntityRepository $repo) {
                return $repo->createQueryBuilder('q');
            }
        );
        $form = $this->factory->createBuilder()
            ->add('dummy', new AutocompleteType($this->registry), $dummyOpts)
            ->add('example', new AutocompleteType($this->registry), $exampleOpts)
            ->getForm();
        $form->submit(array(
            'dummy' => 5,
            'example' => 5
        ));
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals("test 5", $form->get('dummy')->getData()->getName());
        $this->assertEquals("address 15", $form->get('example')->getData()->getAddress());
    }

    public function testMultipleDerivedTypeInSingleForm()
    {
        $dummyOpts = array();
        $exampleOpts = array();
        $form = $this->factory->createBuilder()
            ->add('dummy', new DummyType(), $dummyOpts)
            ->add('example', new ExampleType(), $exampleOpts)
            ->getForm();
        $view = $form->createView();
        $dummyChoices = $view->children['dummy']->vars['choices'];
        $exampleChoices = $view->children['example']->vars['choices'];
        $this->assertEquals($dummyChoices[3]->value, '3');
        $this->assertEquals($dummyChoices[3]->label, 'test 3');
        $this->assertEquals($exampleChoices[7]->value, '7');
        $this->assertEquals($exampleChoices[7]->label, 'address 17');
        $form->submit(array(
            'dummy' => 5,
            'example' => 5
        ));
        $this->assertTrue($form->isSynchronized());
        $this->assertNotEquals(null, $form->get('dummy'));
        $this->assertEquals("test 5", $form->get('dummy')->getData()->getName());
        $this->assertNotEquals(null, $form->get('example'));
        $this->assertEquals("address 15", $form->get('example')->getData()->getAddress());
    }

    public function createAutocompleteForm($maxResults = self::MAX_RESULTS, $multiple = false)
    {
        $options = array(
            'multiple'       => $multiple,
            'class'          => DummyEntity::class,
            'choice_label'   => function(DummyEntity $test) {
                return $test->getId();
            },
            'max_results'    => $maxResults,
            'query_builder'  => function(EntityRepository $repo) {
                return $repo->createQueryBuilder('q');
            },
            'set_handler'    => function(AutocompleteFormEvent $e) use($multiple) {
                $qb = $e->getQueryBuilder();
                if(!$e->getFormEvent()->getData()) {
                } else if($multiple) {
                    $entities = $e->getFormEvent()->getData();
                    $ids = array();

                    foreach($entities as $entity) {
                        array_push($ids, $entity->getId());
                    }
                    $qb->where($qb->expr()->in('q.id', ':ids'))
                        ->setParameter('ids', $ids);
                } else if($e->getFormEvent()->getData()) {
                    $qb->where('q.id = :id')
                        ->setParameter('id', $e->getFormEvent()->getData()->getId());
                }
                return $qb;
            },
            'submit_handler' => function(AutocompleteFormEvent $e) use($multiple) {
                $qb = $e->getQueryBuilder();
                if($multiple) {
                    $qb->where($qb->expr()->in('q.id', ':id'));
                } else {
                    $qb->where('q.id = :id');
                }

                return $qb->setParameter('id', $e->getFormEvent()->getData());
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
            ->with($this->isType('string'))
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
