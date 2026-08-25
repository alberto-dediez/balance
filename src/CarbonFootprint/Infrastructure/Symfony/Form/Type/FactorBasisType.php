<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\Mobility\FactorBasis;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class FactorBasisType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => FactorBasis::class,
            'choice_label' => fn(?FactorBasis $case) => $case?->value,
            'choice_value' => fn(?FactorBasis $case) => $case?->value,
            'translation_domain' => 'factorBasisType',
        ]);
    }

    public function getParent(): string
    {
        return EnumType::class;
    }
}
