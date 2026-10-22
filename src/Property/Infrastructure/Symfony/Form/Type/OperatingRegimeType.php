<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Form\Type;

use App\Property\Domain\Model\Property\OperatingRegime;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class OperatingRegimeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (OperatingRegime::cases() as $operatingRegime) {
            $choices[$operatingRegime->value] = $operatingRegime;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'choice_value' => static fn(?OperatingRegime $operatingRegime): ?string => $operatingRegime?->value,
            'translation_domain' => 'operatingRegime',
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
