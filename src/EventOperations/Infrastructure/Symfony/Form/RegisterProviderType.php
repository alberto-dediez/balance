<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\Symfony\Form;

use App\EventOperations\Application\Provider\Register\RegisterProviderRequest;
use App\Shared\Infrastructure\Symfony\Form\Type\LocaleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class RegisterProviderType extends AbstractType
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
            ->add('vatNumber', TextType::class, [
                'label' => new TranslatableMessage('CIF'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 100),
                ],
            ])
            ->add('street', TextType::class, [
                'label' => new TranslatableMessage('Calle'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 512),
                ],
            ])
            ->add('postalCode', TextType::class, [
                'label' => new TranslatableMessage('Código postal'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 20),
                ],
            ])
            ->add('city', TextType::class, [
                'label' => new TranslatableMessage('Ciudad'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('country', TextType::class, [
                'label' => new TranslatableMessage('País'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => new TranslatableMessage('Email de contacto'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('contactName', TextType::class, [
                'label' => new TranslatableMessage('Nombre de contacto'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('preferredLocale', LocaleType::class, [
                'label' => new TranslatableMessage('Idioma preferido'),
                'required' => false,
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterProviderRequest::class,
        ]);
    }
}
