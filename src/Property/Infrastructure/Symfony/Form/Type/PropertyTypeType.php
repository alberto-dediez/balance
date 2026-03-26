<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Form\Type;

use App\Property\Domain\Model\Property\PropertyType;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class PropertyTypeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (PropertyType::cases() as $propertyType) {
            $choices[$propertyType->value] = $propertyType;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'translation_domain' => 'propertyType',
        ]);
    }

    public function getParent()
    {
        return AutocompleteType::class;
    }
}
