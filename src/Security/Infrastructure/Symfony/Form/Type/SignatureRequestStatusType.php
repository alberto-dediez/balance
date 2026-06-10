<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form\Type;

use App\Security\Domain\Model\SignatureRequest\SignatureRequestStatus;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class SignatureRequestStatusType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach (SignatureRequestStatus::cases() as $case) {
            $choices[$case->value] = $case;
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'choice_value' => fn(?SignatureRequestStatus $case) => $case?->value,
            'translation_domain' => 'signatureRequestStatus',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
