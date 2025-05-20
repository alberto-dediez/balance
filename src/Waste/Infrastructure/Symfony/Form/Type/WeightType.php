<?php

namespace App\Waste\Infrastructure\Symfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Shared\Infrastructure\Symfony\Form\Type\NumberUnitType;

class WeightType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'symbol' => 'kg',
        ]);
    }

    public function getParent(): string
    {
        return NumberUnitType::class;
    }
}