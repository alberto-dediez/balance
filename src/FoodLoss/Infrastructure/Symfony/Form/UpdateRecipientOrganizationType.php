<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Application\RecipientOrganization\Update\UpdateRecipientOrganizationRequest;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\PickupTypeType;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\RecipientOrganizationTypeType;
use App\Security\Infrastructure\Symfony\Form\Type\NotificationChannelType;
use App\Shared\Infrastructure\Symfony\Form\Type\LocaleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class UpdateRecipientOrganizationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('type', RecipientOrganizationTypeType::class, [
                'label' => new TranslatableMessage('Tipo'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('nif', TextType::class, [
                'label' => new TranslatableMessage('NIF'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [new Assert\Length(max: 50)],
            ])
            ->add('socialReason', TextType::class, [
                'label' => new TranslatableMessage('Razón social'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [new Assert\Length(max: 255)],
            ])
            ->add('address', TextareaType::class, [
                'label' => new TranslatableMessage('Dirección'),
                'required' => false,
                'empty_data' => '',
            ])
            ->add('contactName', TextType::class, [
                'label' => new TranslatableMessage('Nombre del contacto'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [new Assert\Length(max: 255)],
            ])
            ->add('contactEmail', EmailType::class, [
                'label' => new TranslatableMessage('Email del contacto'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Email(),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('contactPhone', TextType::class, [
                'label' => new TranslatableMessage('Teléfono del contacto'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [new Assert\Length(max: 50)],
            ])
            ->add('contactLocale', LocaleType::class, [
                'label' => new TranslatableMessage('Idioma del contacto'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Selecciona un idioma'),
            ])
            ->add('contactNotificationChannel', NotificationChannelType::class, [
                'label' => new TranslatableMessage('Canal de notificación preferido'),
                'required' => true,
            ])
            ->add('operatingHours', TextType::class, [
                'label' => new TranslatableMessage('Horario operativo'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [new Assert\Length(max: 255)],
            ])
            ->add('pickupTypes', PickupTypeType::class, [
                'label' => new TranslatableMessage('Tipo de recogida'),
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar'),
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateRecipientOrganizationRequest::class,
        ]);
    }
}
