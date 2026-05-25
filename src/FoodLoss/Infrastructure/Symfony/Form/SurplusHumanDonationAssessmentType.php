<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Infrastructure\Symfony\Form\Type\ReasonIdType;
use App\Shared\Infrastructure\Symfony\Form\Type\BooleanType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class SurplusHumanDonationAssessmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('canBeHumanDonated', BooleanType::class, [
                'label' => new TranslatableMessage('¿Este excedente es apto y seguro para ser donado a entidades sociales?'),
                'required' => true,
                'placeholder' => new TranslatableMessage('Seleccionar'),
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('reasonId', ReasonIdType::class, [
                'label' => new TranslatableMessage('Motivo por el que no se puede donar'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Seleccionar motivo (opcional)'),
                'row_attr' => [
                    'data-controller' => 'showable',
                    'data-showable-selector-value' => '[name$="[canBeHumanDonated]"]',
                    'data-showable-condition-value' => '0',
                ],
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir'),
            ])
            ->add('continue', SubmitType::class, [
                'label' => new TranslatableMessage('Continuar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
