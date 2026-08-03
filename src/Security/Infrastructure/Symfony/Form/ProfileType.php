<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form;

use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaType;
use App\Library\Infrastructure\Symfony\Form\Type\MediaPickerType;
use App\Shared\Infrastructure\Symfony\Form\Type\LocaleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @extends AbstractType<mixed>
 */
class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('picture', MediaPickerType::class, [
                'label' => new TranslatableMessage('Imagen de perfil'),
                'required' => false,
                'mapped' => false,
                'types' => [MediaType::Image],
                'accept' => 'image/*',
                'placeholder' => new TranslatableMessage('No tienes ninguna imagen de perfil'),
                'data' => $options['picture_id'],
            ])
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'picture_id' => null,
        ]);

        $resolver->setAllowedTypes('picture_id', ['null', MediaId::class]);
    }
}
