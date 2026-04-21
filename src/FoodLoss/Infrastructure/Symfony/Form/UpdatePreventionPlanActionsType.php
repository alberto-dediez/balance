<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Application\PreventionPlan\UpdateActions\UpdatePreventionPlanActionsRequest;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\ProposedActionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<mixed> */
class UpdatePreventionPlanActionsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('proposedActions', CollectionType::class, [
                'entry_type' => ProposedActionType::class,
                'entry_options' => [
                    'allowed_property_ids' => $options['allowed_property_ids'],
                ],
                'label' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
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
            'data_class' => UpdatePreventionPlanActionsRequest::class,
            'allowed_property_ids' => [],
        ]);

        $resolver->setAllowedTypes('allowed_property_ids', 'array');
    }
}
