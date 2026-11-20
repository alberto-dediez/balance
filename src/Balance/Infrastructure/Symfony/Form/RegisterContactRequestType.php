<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<RegisterContactRequestFormData> */
class RegisterContactRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre y apellidos'),
                'required' => true,
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)],
            ])
            ->add('email', EmailType::class, [
                'label' => new TranslatableMessage('Email'),
                'required' => true,
                'constraints' => [new Assert\NotBlank(), new Assert\Email(), new Assert\Length(max: 255)],
            ])
            ->add('phone', TelType::class, [
                'label' => new TranslatableMessage('Teléfono'),
                'required' => false,
                'constraints' => [new Assert\Length(max: 40)],
            ])
            ->add('country', ChoiceType::class, [
                'label' => new TranslatableMessage('País'),
                'required' => true,
                'placeholder' => new TranslatableMessage('Elige tu país'),
                'choices' => array_flip($options['countries']),
                'choice_translation_domain' => false,
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('isCompany', CheckboxType::class, [
                'label' => new TranslatableMessage('Aporto como empresa'),
                'required' => false,
            ])
            ->add('companyName', TextType::class, [
                'label' => new TranslatableMessage('Razón social'),
                'required' => false,
                'constraints' => [new Assert\Length(max: 255)],
            ])
            ->add('vatNumber', TextType::class, [
                'label' => new TranslatableMessage('NIF / NIF-IVA'),
                'required' => false,
                'constraints' => [new Assert\Length(max: 40)],
            ])
            ->add('comment', TextareaType::class, [
                'label' => new TranslatableMessage('Comentarios'),
                'required' => false,
                'constraints' => [new Assert\Length(max: 2000)],
            ])
            ->add('send', SubmitType::class, [
                'label' => new TranslatableMessage('Enviar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => RegisterContactRequestFormData::class,
                'csrf_token_id' => 'register_contact_request',
                'countries' => [],
            ])
            ->setAllowedTypes('countries', 'array')
        ;
    }
}
