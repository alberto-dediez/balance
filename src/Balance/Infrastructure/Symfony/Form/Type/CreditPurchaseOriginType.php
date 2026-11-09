<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form\Type;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseOrigin;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class CreditPurchaseOriginType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (CreditPurchaseOrigin::cases() as $case) {
            $choices[$case->value] = $case;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'choice_value' => fn(?CreditPurchaseOrigin $case) => $case?->value,
            'translation_domain' => 'creditPurchaseOrigin',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
