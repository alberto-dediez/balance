<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Application\Incidence\Update\UpdateIncidenceRequest;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\IncidenceEvidenceType;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\IncidenceTypeType;
use App\Property\Infrastructure\Symfony\Form\Type\PropertyIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class UpdateIncidenceType extends AbstractType
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
            ->add('exceptionOccurredOn', DateTimeType::class, [
                'label' => new TranslatableMessage('Fecha y hora'),
                'required' => true,
                'widget' => 'single_text',
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('incidenceType', IncidenceTypeType::class, [
                'label' => new TranslatableMessage('Tipología'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('description', TextareaType::class, [
                'label' => new TranslatableMessage('Descripción'),
                'required' => true,
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('effect', TextareaType::class, [
                'label' => new TranslatableMessage('Efecto o daño causado'),
                'required' => true,
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('propertyId', PropertyIdType::class, [
                'label' => new TranslatableMessage('Propiedad'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('evidences', CollectionType::class, [
                'entry_type' => IncidenceEvidenceType::class,
                'label' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'mapped' => false,
                'data' => $options['existing_evidence_datas'],
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
            'data_class' => UpdateIncidenceRequest::class,
            'existing_evidence_datas' => [],
        ]);

        $resolver->setAllowedTypes('existing_evidence_datas', 'array');
    }
}
