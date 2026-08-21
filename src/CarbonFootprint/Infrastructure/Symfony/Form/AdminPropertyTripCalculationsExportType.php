<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\TripCalculation\Export\PropertyTripCalculationsExportFilter;
use App\Property\Infrastructure\Symfony\Form\Type\PropertyIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<PropertyTripCalculationsExportFilter> */
class AdminPropertyTripCalculationsExportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('propertyId', PropertyIdType::class, [
                'label' => new TranslatableMessage('Instalación'),
                'required' => true,
            ])
            ->add('from', DateType::class, [
                'label' => new TranslatableMessage('Desde'),
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('to', DateType::class, [
                'label' => new TranslatableMessage('Hasta'),
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PropertyTripCalculationsExportFilter::class,
        ]);
    }
}
