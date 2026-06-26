<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\Material\Update\UpdateMaterialRequest;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\MaterialCategoryType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\MaterialEmissionFactorType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\UnitType;
use App\CarbonFootprint\Infrastructure\Symfony\Validator\UniqueEmissionFactorYears;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class UpdateMaterialType extends AbstractType
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
            ->add('category', MaterialCategoryType::class, [
                'label' => new TranslatableMessage('Categoría'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('unit', UnitType::class, [
                'label' => new TranslatableMessage('Unidad'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('unitWeight', TextType::class, [
                'label' => new TranslatableMessage('Peso por unidad (kg)'),
                'required' => false,
                'attr' => [
                    'step' => 'any',
                    'min' => 0,
                    'data-controller' => 'showable',
                    'data-showable-selector-value' => '[name*="[unit]"]',
                    'data-showable-condition-value' => 'ud',
                    'data-showable-container-value' => 'div:not(.input-group)',
                ],
                'constraints' => [new Assert\Type(type: 'numeric')],
            ])
            ->add('emissionFactors', CollectionType::class, [
                'label' => new TranslatableMessage('Factores de emisión'),
                'entry_type' => MaterialEmissionFactorType::class,
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
            'data_class' => UpdateMaterialRequest::class,
        ]);
    }
}
