<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form\Type;

use App\Waste\Domain\Model\Container\MeasurementMethod;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MeasurementMethodType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (MeasurementMethod::cases() as $measurementMethod) {
            $choices[$measurementMethod->value] = $measurementMethod;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'translation_domain' => 'measurementMethod',
            'choice_attr' => fn(?MeasurementMethod $method): array => $method instanceof MeasurementMethod ? ['data-value' => $method->value] : [],
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
