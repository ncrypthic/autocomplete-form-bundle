<?php
namespace LLA\AutocompleteFormBundle\Tests\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use LLA\AutocompleteFormBundle\Tests\Entity\ExampleEntity;
use Doctrine\ORM\EntityRepository;

/**
 * Example form type
 */
class ExampleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options) 
    {}

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'class'         => ExampleEntity::class,
            'choice_label'  => 'address',
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
