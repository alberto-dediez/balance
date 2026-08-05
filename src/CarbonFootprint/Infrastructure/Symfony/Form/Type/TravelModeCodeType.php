<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class TravelModeCodeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (TravelMode::cases() as $case) {
            $choices[$case->value] = $case->value;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'translation_domain' => 'travelModeType',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
