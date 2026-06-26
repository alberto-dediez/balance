<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Unit selector for the DataExchange import flow: the bound value is the unit code as a
 * plain string (e.g. "kg"), not the enum instance, because the importation
 * translation/assign-values steps persist scalar values only.
 *
 * @extends AbstractType<mixed>
 */
class UnitCodeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (Unit::cases() as $case) {
            $choices[$case->value] = $case->value;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'translation_domain' => 'unitType',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
