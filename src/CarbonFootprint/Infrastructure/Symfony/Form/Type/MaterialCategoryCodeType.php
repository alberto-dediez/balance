<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\Material\MaterialCategory;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class MaterialCategoryCodeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (MaterialCategory::cases() as $case) {
            $choices[$case->value] = $case->value;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'translation_domain' => 'materialCategoryType',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
