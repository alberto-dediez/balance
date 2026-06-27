<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\Waste\Update\UpdateWasteRequest;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\UnitType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\WasteEmissionFactorType;
use App\CarbonFootprint\Infrastructure\Symfony\Validator\UniqueEmissionFactorYears;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class UpdateWasteType extends AbstractType
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
            ->add('unit', UnitType::class, [
                'label' => new TranslatableMessage('Unidad'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('density', NumberType::class, [
                'label' => new TranslatableMessage('Densidad'),
                'required' => true,
                'scale' => 4,
                'html5' => true,
                'attr' => ['step' => '0.0001', 'min' => 0],
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\GreaterThan(0),
                ],
            ])
            ->add('emissionFactors', CollectionType::class, [
                'label' => new TranslatableMessage('Factores de emisión'),
                'entry_type' => WasteEmissionFactorType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'required' => false,
                'constraints' => [new UniqueEmissionFactorYears()],
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir'),
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateWasteRequest::class,
        ]);
    }
}
