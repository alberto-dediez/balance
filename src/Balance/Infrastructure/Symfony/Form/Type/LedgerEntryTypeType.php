<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form\Type;

use App\Balance\Domain\Model\LedgerEntry\LedgerEntryType;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class LedgerEntryTypeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (LedgerEntryType::cases() as $case) {
            $choices[$case->value] = $case;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'choice_value' => fn(?LedgerEntryType $case) => $case?->value,
            'translation_domain' => 'ledgerEntryType',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
