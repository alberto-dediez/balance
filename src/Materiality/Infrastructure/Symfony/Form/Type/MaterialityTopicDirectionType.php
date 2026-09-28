<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\Symfony\Form\Type;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicDirection;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class MaterialityTopicDirectionType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (MaterialityTopicDirection::cases() as $case) {
            $choices[$case->value] = $case;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'choice_value' => fn(?MaterialityTopicDirection $case) => $case?->value,
            'translation_domain' => 'materialityTopicDirectionType',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
