<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use App\FoodLoss\Application\PreventionPlan\UpdateActions\ProposedActionData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class ProposedActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('propertyId', FilteredPropertyIdType::class, [
                'label' => new TranslatableMessage('Propiedad'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
                'allowed_property_ids' => $options['allowed_property_ids'],
            ])
            ->add('actionId', ActionIdType::class, [
                'label' => new TranslatableMessage('Acción'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProposedActionData::class,
            'allowed_property_ids' => [],
        ]);

        $resolver->setAllowedTypes('allowed_property_ids', 'array');
    }
}
