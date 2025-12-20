<?php

namespace App\Waste\Infrastructure\Symfony\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use App\Waste\Infrastructure\Symfony\Form\Type\PurposeType;
use App\Waste\Infrastructure\Symfony\Form\Type\WasteIdType;
use App\Waste\Infrastructure\Symfony\Form\Type\PropertyIdType;
use App\Waste\Infrastructure\Symfony\Form\Type\WasteManagerIdType;
use App\Waste\Application\DailyWaste\Statistics\StatisticsDailyWasteRequest;

class StatisticsDailyWasteType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('properties', PropertyIdType::class, [
                'label' => new TranslatableMessage('Propiedades'),
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'placeholder' => new TranslatableMessage('Todas las propiedades')
                ]
            ])
            ->add('wasteId', WasteIdType::class, [
                'label' => new TranslatableMessage('Tipología de residuo'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas las tipologías')
            ])
            ->add('purpose', PurposeType::class, [
                'label' => new TranslatableMessage('Destino'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos los destinos')
            ])
            ->add('wasteManagerId', WasteManagerIdType::class, [
                'label' => new TranslatableMessage('Gestor de residuos'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos los gestores')
            ])
            ->add('dayFrom', DateType::class, [
                'label' => new TranslatableMessage('Fecha desde'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas las fechas')
            ])
            ->add('dayTo', DateType::class, [
                'label' => new TranslatableMessage('Fecha hasta'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas las fechas')
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => StatisticsDailyWasteRequest::class
        ]);
    }
}
