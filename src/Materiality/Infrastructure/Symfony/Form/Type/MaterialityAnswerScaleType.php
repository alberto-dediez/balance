<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Symfony\Form\Type;

use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityAnswerScale;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class MaterialityAnswerScaleType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (MaterialityAnswerScale::cases() as $case) {
            $choices[$case->value] = $case;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'choice_value' => fn(?MaterialityAnswerScale $case) => $case?->value,
            'translation_domain' => 'materialityAnswerScaleType',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
