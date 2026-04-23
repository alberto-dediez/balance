<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Application\PreventionPlan\Update\UpdatePreventionPlanRequest;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\PropertyIdsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class UpdatePreventionPlanType extends AbstractType
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
            ->add('properties', PropertyIdsType::class, [
                'label' => new TranslatableMessage('Propiedades'),
                'placeholder' => new TranslatableMessage('Todas las propiedades'),
                'constraints' => [
                    new Assert\Count(min: 1, minMessage: 'Debes seleccionar al menos una propiedad'),
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Continuar'),
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdatePreventionPlanRequest::class,
        ]);
    }
}
