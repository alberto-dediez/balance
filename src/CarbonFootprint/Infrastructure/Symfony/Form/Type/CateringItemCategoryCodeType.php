<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemCategory;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class CateringItemCategoryCodeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (CateringItemCategory::cases() as $case) {
            $choices[$case->value] = $case->value;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'translation_domain' => 'cateringItemCategoryType',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
