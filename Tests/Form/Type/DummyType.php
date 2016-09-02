<?php
namespace LLA\AutocompleteFormBundle\Tests\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use LLA\AutocompleteFormBundle\Tests\Entity\DummyEntity;
use Doctrine\ORM\EntityRepository;

/**
 * Dummy form type
 */
class DummyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) 
    {}

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'class'         => DummyEntity::class,
            'choice_label'  => 'name',
            'max_results'   => 10,
            'query_builder' => function(EntityRepository $repo) {
                return $repo->createQueryBuilder('q');
            }
        ));
    }

    public function getParent() 
    {
        // return '\LLA\AutocompleteFormBundle\Form\Type\Autocomplete\AutocompleteType';
        return 'autocomplete';
    }
}
