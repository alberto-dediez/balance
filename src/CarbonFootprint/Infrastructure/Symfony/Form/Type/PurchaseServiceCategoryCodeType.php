<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceCategory;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class PurchaseServiceCategoryCodeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (PurchaseServiceCategory::cases() as $case) {
            $choices[$case->value] = $case->value;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'translation_domain' => 'purchaseServiceCategoryType',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
