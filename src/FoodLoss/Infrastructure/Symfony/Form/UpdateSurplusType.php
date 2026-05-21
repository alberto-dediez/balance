<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Application\Surplus\Update\UpdateSurplusRequest;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\CriticalPointIdType;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\DeliveryLogUnitType;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\FoodTypeType;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\ServiceCategoryIdType;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\SurplusCauseIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class UpdateSurplusType extends AbstractType
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
            ->add('serviceCategoryId', ServiceCategoryIdType::class, [
                'label' => new TranslatableMessage('Turno'),
                'required' => true,
                'placeholder' => new TranslatableMessage('Seleccionar turno'),
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('criticalPointId', CriticalPointIdType::class, [
                'label' => new TranslatableMessage('Punto crítico de origen'),
                'required' => true,
                'placeholder' => new TranslatableMessage('Seleccionar punto crítico'),
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('foodType', FoodTypeType::class, [
                'label' => new TranslatableMessage('Tipo de alimento'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('surplusCauseId', SurplusCauseIdType::class, [
                'label' => new TranslatableMessage('Motivo'),
                'required' => true,
                'placeholder' => new TranslatableMessage('Seleccionar motivo'),
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('quantity', NumberType::class, [
                'label' => new TranslatableMessage('Cantidad'),
                'required' => true,
                'scale' => 2,
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('unit', DeliveryLogUnitType::class, [
                'label' => new TranslatableMessage('Unidad'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
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
            'data_class' => UpdateSurplusRequest::class,
        ]);
    }
}
