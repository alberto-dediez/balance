<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Infrastructure\Symfony\Form\Type\PickupTypeType;
use App\FoodLoss\Infrastructure\UI\Web\DeliveryLog\Bulk\BulkDeliveryLogRequest;
use App\FoodLoss\Infrastructure\UI\Web\DeliveryLog\Bulk\DeliverBulkAction;
use App\FoodLoss\Infrastructure\UI\Web\DeliveryLog\Bulk\DeliverWithSignatureBulkAction;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class BulkDeliveryLogType extends BulkType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('deliveredAt', DateType::class, [
                'label' => new TranslatableMessage('Fecha de entrega'),
                'required' => true,
                'widget' => 'single_text',
            ])
            ->add('pickupType', PickupTypeType::class, [
                'label' => new TranslatableMessage('Tipo de recogida'),
                'required' => true,
                'multiple' => false,
                'expanded' => false,
                'placeholder' => new TranslatableMessage('Tipo de recogida'),
                'translation_domain' => 'pickupType',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'actions' => [
                new DeliverBulkAction(),
                new DeliverWithSignatureBulkAction(),
            ],
            'data_class' => BulkDeliveryLogRequest::class,
        ]);
    }
}
