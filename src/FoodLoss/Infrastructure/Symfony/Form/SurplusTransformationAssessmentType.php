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

/** @extends AbstractType<mixed> */
class SurplusTransformationAssessmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('canBeTransformed', BooleanType::class, [
                'label' => new TranslatableMessage('¿Se puede transformar en el propio establecimiento? (Ej. fruta madura para mermeladas, pan de ayer para pan rallado).'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Seleccionar'),
            ])
            ->add('reasonId', ReasonIdType::class, [
                'label' => new TranslatableMessage('Motivo por el que no se puede transformar'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Seleccionar motivo (opcional)'),
                'row_attr' => [
                    'data-controller' => 'showable',
                    'data-showable-selector-value' => '[name$="[canBeTransformed]"]',
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
