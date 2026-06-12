<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Symfony\Form;

use App\FoodLoss\Application\Dashboard\GetFoodLossDashboardRequest;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogUnit;
use App\FoodLoss\Infrastructure\Symfony\Form\Type\DeliveryLogUnitType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use App\Shared\Infrastructure\Symfony\Form\Type\NumberUnitType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class DashboardFoodLossType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('displayUnit', DeliveryLogUnitType::class, [
                'label' => new TranslatableMessage('Ver en'),
                'required' => true,
            ])
            ->add('kgPerRation', NumberUnitType::class, [
                'label' => new TranslatableMessage('Factor de conversión'),
                'required' => true,
                'symbol' => 'kg/ración',
                'scale' => 2,
                'empty_data' => (string) DeliveryLogUnit::MITECO_KG_PER_RATION,
                'help' => new TranslatableMessage('Kg equivalentes a una ración. Por defecto, el valor recomendado por las guías del MITECO.'),
            ])
            ->add('dateFrom', DateType::class, [
                'label' => new TranslatableMessage('Fecha desde'),
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('dateTo', DateType::class, [
                'label' => new TranslatableMessage('Fecha hasta'),
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GetFoodLossDashboardRequest::class,
        ]);
    }
}
