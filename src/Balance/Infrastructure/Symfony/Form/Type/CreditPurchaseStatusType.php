<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form\Type;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseStatus;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class CreditPurchaseStatusType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (CreditPurchaseStatus::cases() as $case) {
            $choices[$case->value] = $case;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'choice_value' => fn(?CreditPurchaseStatus $case) => $case?->value,
            'translation_domain' => 'creditPurchaseStatus',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
