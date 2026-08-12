<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\PublicLanding\Update\UpdatePublicLandingRequest;
use App\Library\Domain\Model\Media\MediaType;
use App\Library\Infrastructure\Symfony\Form\Type\MediaPickerType;
use App\Shared\Infrastructure\Symfony\Form\Type\MarkdownEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class UpdatePublicLandingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('active', CheckboxType::class, [
                'label' => new TranslatableMessage('Mostrar la página de bienvenida antes del formulario'),
                'required' => false,
            ])
            ->add('heroImage', MediaPickerType::class, [
                'label' => new TranslatableMessage('Imagen principal'),
                'required' => false,
                'types' => [MediaType::Image],
            ])
            ->add('title', TextType::class, [
                'label' => new TranslatableMessage('Título'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('subtitle', TextType::class, [
                'label' => new TranslatableMessage('Subtítulo'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('intro', MarkdownEditorType::class, [
                'label' => new TranslatableMessage('Introducción'),
                'required' => false,
                'empty_data' => '',
                'height' => '260px',
            ])
            ->add('blocks', CollectionType::class, [
                'label' => false,
                'entry_type' => PublicLandingBlockType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'required' => false,
            ])
            ->add('ctaLabel', TextType::class, [
                'label' => new TranslatableMessage('Texto del botón'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdatePublicLandingRequest::class,
        ]);
    }
}
