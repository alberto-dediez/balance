<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class TravelModeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => TravelMode::class,
            'choice_value' => fn(?TravelMode $case) => $case?->value,
            'translation_domain' => 'travelModeType',
        ]);
    }

    public function getParent(): string
    {
        return EnumType::class;
    }
}
