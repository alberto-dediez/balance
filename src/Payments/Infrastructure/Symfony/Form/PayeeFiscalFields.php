<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Symfony\Form;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class PayeeFiscalFields
{
    /** @param FormBuilderInterface<mixed> $builder */
    public static function add(FormBuilderInterface $builder, bool $includeNotes = true): void
    {
        $builder
            ->add('legalName', TextType::class, [
                'label' => new TranslatableMessage('Razón social'),
                'required' => true,
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)],
            ])
            ->add('taxId', TextType::class, [
                'label' => new TranslatableMessage('NIF / CIF'),
                'required' => false,
                'constraints' => [new Assert\Length(max: 50)],
            ])
            ->add('street', TextType::class, [
                'label' => new TranslatableMessage('Dirección'),
                'required' => false,
            ])
            ->add('postalCode', TextType::class, [
                'label' => new TranslatableMessage('Código postal'),
                'required' => false,
            ])
            ->add('city', TextType::class, [
                'label' => new TranslatableMessage('Ciudad'),
                'required' => false,
            ])
            ->add('country', TextType::class, [
                'label' => new TranslatableMessage('País'),
                'required' => false,
            ])
            ->add('iban', TextType::class, [
                'label' => new TranslatableMessage('IBAN'),
                'required' => false,
                'constraints' => [new Assert\Iban()],
            ])
            ->add('bic', TextType::class, [
                'label' => new TranslatableMessage('BIC'),
                'required' => false,
                'constraints' => [new Assert\Bic()],
            ])
            ->add('holderName', TextType::class, [
                'label' => new TranslatableMessage('Titular de la cuenta'),
                'required' => false,
                'constraints' => [new Assert\Length(max: 255)],
            ])
        ;

        if ($includeNotes) {
            $builder->add('notes', TextareaType::class, [
                'label' => new TranslatableMessage('Notas'),
                'required' => false,
            ]);
        }
    }
}
