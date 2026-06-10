<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form\Type;

use App\Waste\Domain\Model\Alert\AlertType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<array<string, mixed>> */
class AlertTypeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (AlertType::cases() as $alertType) {
            $choices[$alertType->value] = $alertType;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'translation_domain' => 'alertType',
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
