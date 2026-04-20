<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form\Type;

use App\FoodLoss\Application\PreventionPlan\UpdateActions\ProposedActionData;
use App\Property\Domain\Model\Property\PropertyId;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
                'attr' => ['data-action' => 'change->proposed-action#propertyChanged'],
            ])
            ->add('actionId', ActionIdType::class, [
                'label' => new TranslatableMessage('Acción'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $data = $event->getData();
            $form = $event->getForm();

            $propertyId = $data instanceof ProposedActionData ? $data->propertyId : PropertyId::generate();

            $form->add('criticalPointId', CriticalPointIdType::class, [
                'label' => new TranslatableMessage('Punto crítico'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
                'property_id' => $propertyId,
                'attr' => ['data-role' => 'critical-point-select'],
            ]);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            $form = $event->getForm();

            $propertyIdStr = $data['propertyId'] ?? null;
            $propertyId = null;

            if (!empty($propertyIdStr)) {
                try {
                    $propertyId = new PropertyId($propertyIdStr);
                } catch (\Throwable) {
                    // invalid UUID — leave null
                }
            }

            $form->add('criticalPointId', CriticalPointIdType::class, [
                'label' => new TranslatableMessage('Punto crítico'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
                'property_id' => $propertyId,
                'attr' => ['data-role' => 'critical-point-select'],
            ]);
        });
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
