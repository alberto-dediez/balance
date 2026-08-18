<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form\Type;

use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class LedgerBeneficiaryType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (LedgerBeneficiary::cases() as $case) {
            $choices[$case->value] = $case;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'choice_value' => fn(?LedgerBeneficiary $case) => $case?->value,
            'translation_domain' => 'ledgerBeneficiary',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
