<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Application\DeliveryLog\Register\RegisterDeliveryLogRequest;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\DeliveryLogUnitType;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\RecipientOrganizationIdType;
use App\Property\Infrastructure\Symfony\Form\Type\PropertyIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class RegisterDeliveryLogType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'label' => new TranslatableMessage('Fecha'),
                'required' => true,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('quantity', NumberType::class, [
                'label' => new TranslatableMessage('Cantidad'),
                'required' => true,
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('unit', DeliveryLogUnitType::class, [
                'label' => new TranslatableMessage('Unidad'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('productType', TextType::class, [
                'label' => new TranslatableMessage('Tipo de producto'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('batch', TextType::class, [
                'label' => new TranslatableMessage('Lote'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [new Assert\Length(max: 255)],
            ])
            ->add('recipientOrganizationId', RecipientOrganizationIdType::class, [
                'label' => new TranslatableMessage('Entidad receptora'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('propertyId', PropertyIdType::class, [
                'label' => new TranslatableMessage('Propiedad'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterDeliveryLogRequest::class,
        ]);
    }
}
