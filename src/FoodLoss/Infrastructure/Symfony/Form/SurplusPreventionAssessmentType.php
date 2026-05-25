<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\ActionIdType;
use App\Shared\Infrastructure\Symfony\Form\Type\BooleanType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class SurplusPreventionAssessmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('canBePrevented', BooleanType::class, [
                'label' => new TranslatableMessage('¿Se podía haber prevenido el desperdicio?'),
                'required' => true,
                'placeholder' => new TranslatableMessage('Seleccionar'),
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('actionId', ActionIdType::class, [
                'label' => new TranslatableMessage('Acción de prevención'),
                'required' => false,
                'help' => new TranslatableMessage('La acción escogida se agregará al punto crítico para prevenir futuros excedentes como este'),
                'action_priority' => Priority::Prevention,
                'placeholder' => new TranslatableMessage('Seleccionar acción (opcional)'),
                'row_attr' => [
                    'data-controller' => 'showable',
                    'data-showable-selector-value' => '[name$="[canBePrevented]"]',
                    'data-showable-condition-value' => '1',
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
