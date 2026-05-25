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
class SurplusCompostingAssessmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('canBeComposted', BooleanType::class, [
                'label' => new TranslatableMessage('¿Se destinará a compostaje agrícola o generación de biogás?'),
                'required' => true,
                'placeholder' => new TranslatableMessage('Seleccionar'),
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('reasonId', ReasonIdType::class, [
                'label' => new TranslatableMessage('Motivo por el que no se puede compostar'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Seleccionar motivo (opcional)'),
                'row_attr' => [
                    'data-controller' => 'showable',
                    'data-showable-selector-value' => '[name$="[canBeComposted]"]',
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
