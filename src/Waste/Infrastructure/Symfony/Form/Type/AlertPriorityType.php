<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form\Type;

use App\Waste\Domain\Model\Alert\AlertPriority;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<array<string, mixed>> */
class AlertPriorityType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (AlertPriority::cases() as $alertPriority) {
            $choices[$alertPriority->value] = $alertPriority;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'translation_domain' => 'alertPriority',
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
