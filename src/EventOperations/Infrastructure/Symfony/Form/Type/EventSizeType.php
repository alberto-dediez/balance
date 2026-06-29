<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\Symfony\Form\Type;

use App\EventOperations\Domain\Model\Event\EventSize;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class EventSizeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (EventSize::cases() as $case) {
            $choices[$case->value] = $case;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'choice_value' => fn(?EventSize $case) => $case?->value,
            'translation_domain' => 'eventSizeType',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
