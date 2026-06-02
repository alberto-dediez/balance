<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form;

use App\Waste\Application\AlertConfiguration\Update\UpdateAlertConfigurationRequest;
use App\Waste\Domain\Model\Alert\AlertType;
use App\Waste\Infrastructure\Symfony\Form\AlertConfiguration\AlertConfigurationItemType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<mixed> */
class UpdateAlertConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $itemsBuilder = $builder->create('items', FormType::class, [
            'compound' => true,
            'label' => false,
        ]);

        foreach (AlertType::cases() as $alertType) {
            $itemsBuilder->add($alertType->value, AlertConfigurationItemType::class, [
                'label' => new TranslatableMessage($alertType->value, [], 'alertType'),
                'alert_type' => $alertType,
            ]);
        }

        $builder->add($itemsBuilder);

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
            'data_class' => UpdateAlertConfigurationRequest::class,
        ]);
    }
}
