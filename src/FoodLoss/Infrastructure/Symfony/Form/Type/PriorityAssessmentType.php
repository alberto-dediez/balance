<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use App\FoodLoss\Domain\Model\CriticalPoint\PriorityAssessment;
use App\Shared\Infrastructure\Symfony\Form\Type\BooleanType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<PriorityAssessment> */
class PriorityAssessmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('isPossible', BooleanType::class, [
            'label' => $options['label'],
            'required' => true,
        ]);

        $builder->add('actionIds', ActionIdType::class, [
            'label' => 'Acciones',
            'multiple' => true,
            'required' => false,
            'action_priority' => $options['action_priority'],
            'row_attr' => [
                'data-controller' => 'showable',
                'data-showable-selector-value' => '[name$="[' . $builder->getName() . '][isPossible]"]',
                'data-showable-condition-value' => '1',
            ],
        ]);

        $builder->add('reasonIds', ReasonIdType::class, [
            'label' => 'Motivos',
            'multiple' => true,
            'required' => false,
            'action_priority' => $options['action_priority'],
            'row_attr' => [
                'data-controller' => 'showable',
                'data-showable-selector-value' => '[name$="[' . $builder->getName() . '][isPossible]"]',
                'data-showable-condition-value' => '0',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PriorityAssessment::class,
            'action_priority' => null,
        ]);
    }
}
