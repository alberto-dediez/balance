<?php

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use App\FoodLoss\Domain\Model\Action\Priority;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;

class PriorityType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = [];

        foreach (Priority::cases() as $priorityCase) {
            $choices[$priorityCase->value] = $priorityCase;
        }

        $resolver->setDefaults(array(
            'choices' => $choices,
            'translation_domain' => 'priorityType',
        ));
    }
    
    public function getParent()
    {
        return AutocompleteType::class;
    }
}