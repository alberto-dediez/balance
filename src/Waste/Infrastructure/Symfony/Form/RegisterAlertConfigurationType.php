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
class RegisterAlertConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('accountId', \App\Security\Infrastructure\Symfony\Form\Type\AccountIdType::class, [
            'label' => new TranslatableMessage('Cuenta'),
            'required' => true,
        ]);

        $builder->add('items', CollectionType::class, [
            'entry_type' => AlertConfigurationItemType::class,
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

        $builder->add('register', SubmitType::class, [
            'label' => new TranslatableMessage('Registrar'),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
