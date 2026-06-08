<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form;

use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Infrastructure\Symfony\Form\AlertConfiguration\AlertConfigurationItemData;
use App\Waste\Infrastructure\Symfony\Form\AlertConfiguration\AlertConfigurationItemType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<mixed> */
class UpdateAlertConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('items', CollectionType::class, [
            'entry_type' => AlertConfigurationItemType::class,
            'entry_options' => ['account_id' => $options['account_id']],
            'label' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'prototype_data' => new AlertConfigurationItemData(
                type: AlertType::cases()[0]->value,
                parameters: AlertConfigurationItemType::parameterDefaults(),
            ),
            'constraints' => [AlertConfigurationItemType::uniqueItemsConstraint()],
        ]);

        $builder->add('save', SubmitType::class, [
            'label' => new TranslatableMessage('Guardar'),
        ]);
        $builder->add('saveAndReturn', SubmitType::class, [
            'label' => new TranslatableMessage('Guardar y salir'),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'account_id' => null,
        ]);

        $resolver->setAllowedTypes('account_id', ['null', \App\Security\Domain\Model\Account\AccountId::class]);
    }
}
