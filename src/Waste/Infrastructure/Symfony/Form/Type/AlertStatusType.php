<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form\Type;

use App\Waste\Domain\Model\Alert\AlertStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<array<string, mixed>> */
class AlertStatusType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (AlertStatus::cases() as $alertStatus) {
            $choices[$alertStatus->value] = $alertStatus;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'translation_domain' => 'alertStatus',
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
