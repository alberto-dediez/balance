<?php

namespace App\Waste\Infrastructure\Symfony\Type;

use App\Waste\Domain\Model\Container\MeasurementMethod;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class MeasurementMethodType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = [];

        foreach (MeasurementMethod::cases() as $measurementMethod) {
            $choices[$measurementMethod->value] = $measurementMethod;
        }

        $resolver->setDefaults(array(
            'choices' => $choices,
            'translation_domain' => 'measurementMethod',
        ));
    }
    
    public function getParent()
    {
        return ChoiceType::class;
    }
}