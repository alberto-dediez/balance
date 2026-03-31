<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use App\FoodLoss\Domain\Model\Action\Priority;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class PriorityType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (Priority::cases() as $priorityCase) {
            $choices[$priorityCase->value] = $priorityCase;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'translation_domain' => 'priorityType',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
