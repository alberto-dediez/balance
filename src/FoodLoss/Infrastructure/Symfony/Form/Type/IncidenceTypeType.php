<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use App\FoodLoss\Domain\Model\Incidence\IncidenceType;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class IncidenceTypeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (IncidenceType::cases() as $case) {
            $choices[$case->value] = $case;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'choice_value' => fn(?IncidenceType $case) => $case?->value,
            'translation_domain' => 'incidenceType',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
