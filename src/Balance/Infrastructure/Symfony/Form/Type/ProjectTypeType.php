<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form\Type;

use App\Balance\Domain\Model\Project\ProjectType;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class ProjectTypeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (ProjectType::cases() as $case) {
            $choices[$case->value] = $case;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'choice_value' => fn(?ProjectType $case) => $case?->value,
            'translation_domain' => 'projectType',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
