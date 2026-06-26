<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\RoadTransport\Vehicle;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Vehicle selector for the DataExchange import flow: the bound value is the vehicle code as a
 * plain string (e.g. "passenger_cars"), not the enum instance, because the importation
 * translation/assign-values steps persist scalar values only.
 *
 * @extends AbstractType<mixed>
 */
class RoadTransportVehicleCodeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (Vehicle::cases() as $case) {
            $choices[$case->value] = $case->value;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'translation_domain' => 'roadTransportVehicleType',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
