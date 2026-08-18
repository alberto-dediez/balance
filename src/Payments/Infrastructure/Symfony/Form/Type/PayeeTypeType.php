<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Symfony\Form\Type;

use App\Payments\Domain\Model\Payee\PayeeType;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class PayeeTypeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (PayeeType::cases() as $case) {
            $choices[$case->value] = $case;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'choice_value' => fn(?PayeeType $case) => $case?->value,
            'translation_domain' => 'payeeType',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
