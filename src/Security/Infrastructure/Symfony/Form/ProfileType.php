<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form;

use App\Shared\Infrastructure\Symfony\Form\Type\LocaleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @extends AbstractType<mixed>
 */
class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('preferredLocale', LocaleType::class, [
                'label' => 'Idioma preferido',
                'required' => true,
                'mapped' => false,
                'help' => new TranslatableMessage('Será usado en las comunicaciones fuera de la plataforma'),
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar'),
            ])
        ;
    }
}
