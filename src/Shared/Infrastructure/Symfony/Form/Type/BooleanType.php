<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<bool|string|null> */
class BooleanType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                'No' => false,
                'Sí' => true,
            ],
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
