<?php

namespace App\Waste\Infrastructure\Symfony\Type;

use App\Waste\Domain\Model\Container\Purpose;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class PurposeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = [];

        foreach (Purpose::cases() as $purpose) {
            $choices[$purpose->value] = $purpose;
        }

        $resolver->setDefaults(array(
            'choices' => $choices,
            'translation_domain' => 'purpose',
        ));
    }
    
    public function getParent()
    {
        return ChoiceType::class;
    }
}