<?php

namespace App\Property\Infrastructure\Symfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use App\Property\Domain\Model\Property\PropertyType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;

class PropertyTypeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = [];

        foreach (PropertyType::cases() as $propertyType) {
            $choices[$propertyType->value] = $propertyType;
        }

        $resolver->setDefaults(array(
            'choices' => $choices,
            'translation_domain' => 'propertyType',
        ));
    }
    
    public function getParent()
    {
        return AutocompleteType::class;
    }
}