<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\Symfony\Form\Type;

use App\EventOperations\Domain\Model\Event\EventTypology;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class EventTypologyType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (EventTypology::cases() as $case) {
            $choices[$case->value] = $case;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'choice_value' => fn(?EventTypology $case) => $case?->value,
            'translation_domain' => 'eventTypologyType',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
