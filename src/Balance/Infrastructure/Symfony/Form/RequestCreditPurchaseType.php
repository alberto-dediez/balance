<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form;

use App\Balance\Application\CreditPurchase\Request\RequestCreditPurchaseRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class RequestCreditPurchaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tonnes', NumberType::class, [
                'label' => new TranslatableMessage('Toneladas de CO2 solicitadas'),
                'required' => true,
                'scale' => 2,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                ],
            ])
            ->add('legalName', TextType::class, [
                'label' => new TranslatableMessage('Razón social'),
                'required' => true,
                'empty_data' => '',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)],
            ])
            ->add('taxId', TextType::class, [
                'label' => new TranslatableMessage('NIF / CIF'),
                'required' => true,
                'empty_data' => '',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 50)],
            ])
            ->add('street', TextType::class, [
                'label' => new TranslatableMessage('Dirección'),
                'required' => true,
                'empty_data' => '',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 512)],
            ])
            ->add('postalCode', TextType::class, [
                'label' => new TranslatableMessage('Código postal'),
                'required' => true,
                'empty_data' => '',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 20)],
            ])
            ->add('city', TextType::class, [
                'label' => new TranslatableMessage('Ciudad'),
                'required' => true,
                'empty_data' => '',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)],
            ])
            ->add('country', ChoiceType::class, [
                'label' => new TranslatableMessage('País'),
                'required' => true,
                'empty_data' => '',
                'placeholder' => new TranslatableMessage('Elige tu país'),
                'choices' => array_flip($options['countries']),
                'choice_translation_domain' => false,
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('email', EmailType::class, [
                'label' => new TranslatableMessage('Email para recibir la factura'),
                'required' => true,
                'empty_data' => '',
                'constraints' => [new Assert\NotBlank(), new Assert\Email(), new Assert\Length(max: 255)],
            ])
            ->add('termsAccepted', CheckboxType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\IsTrue(
                        message: 'Debe aceptar las condiciones para solicitar los créditos',
                        groups: ['confirm'],
                    ),
                ],
            ])
            ->add('acceptedTotal', NumberType::class, [
                'required' => false,
                'html5' => true,
                'scale' => 2,
            ])
            ->add('calculate', SubmitType::class, [
                'label' => new TranslatableMessage('Continuar'),
            ])
            ->add('modify', SubmitType::class, [
                'label' => new TranslatableMessage('Modificar datos'),
                'validation_groups' => false,
            ])
            ->add('request', SubmitType::class, [
                'label' => new TranslatableMessage('Solicitar créditos'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RequestCreditPurchaseRequest::class,
            'validation_groups' => static function (FormInterface $form): array {
                $request = $form->get('request');

                return $request instanceof ClickableInterface && $request->isClicked() ? ['Default', 'confirm'] : ['Default'];
            },
        ]);

        $resolver->setRequired('countries');
        $resolver->setAllowedTypes('countries', 'array');
    }
}
