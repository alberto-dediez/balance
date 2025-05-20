<?php

namespace App\Waste\Infrastructure\Symfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Shared\Infrastructure\Symfony\Form\Type\NumberUnitType;

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