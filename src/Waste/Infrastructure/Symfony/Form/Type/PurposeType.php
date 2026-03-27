<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form\Type;

use App\Waste\Domain\Model\Container\Purpose;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<array<string, mixed>> */
class PurposeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (Purpose::cases() as $purpose) {
            $choices[$purpose->value] = $purpose;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'translation_domain' => 'purpose',
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
