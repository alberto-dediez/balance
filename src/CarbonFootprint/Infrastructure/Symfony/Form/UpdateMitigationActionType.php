<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\MitigationActionEffectType;
use App\Shared\Infrastructure\Symfony\Form\Type\MarkdownEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class UpdateMitigationActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $year = $options['year'];

        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('description', MarkdownEditorType::class, [
                'label' => new TranslatableMessage('Descripción'),
                'required' => false,
                'empty_data' => '',
                'height' => '220px',
                'hide_image' => true,
            ])
            ->add('targetYear', IntegerType::class, [
                'label' => new TranslatableMessage('Año objetivo'),
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\GreaterThan($options['base_year']),
                    new Assert\LessThanOrEqual($year + 50),
                ],
            ])
            ->add('effects', CollectionType::class, [
                'label' => false,
                'entry_type' => MitigationActionEffectType::class,
                'entry_options' => [
                    'label' => false,
                    'manual' => $options['manual'],
                    'emission_sources' => $options['emission_sources'],
                    'ranges' => $options['ranges'],
                ],
                'allow_add' => $options['manual'],
                'allow_delete' => $options['manual'],
                'prototype' => $options['manual'],
                'required' => false,
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
        $resolver->setRequired(['year', 'base_year']);
        $resolver->setDefaults([
            'manual' => true,
            'emission_sources' => [],
            'ranges' => [],
        ]);
        $resolver->setAllowedTypes('year', 'int');
        $resolver->setAllowedTypes('base_year', 'int');
        $resolver->setAllowedTypes('manual', 'bool');
        $resolver->setAllowedTypes('emission_sources', 'array');
        $resolver->setAllowedTypes('ranges', 'array');
    }
}
