<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form\Type;

use App\Shared\Infrastructure\Symfony\Form\Type\NumberUnitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<array<string, mixed>> */
class VolumeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'symbol' => 'l',
        ]);
    }

    public function getParent(): string
    {
        return NumberUnitType::class;
    }
}
