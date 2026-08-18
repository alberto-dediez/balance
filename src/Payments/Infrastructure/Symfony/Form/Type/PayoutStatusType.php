<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Symfony\Form\Type;

use App\Payments\Domain\Model\Payout\PayoutStatus;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class PayoutStatusType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (PayoutStatus::cases() as $case) {
            $choices[$case->value] = $case;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'choice_value' => fn(?PayoutStatus $case) => $case?->value,
            'translation_domain' => 'payoutStatus',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
