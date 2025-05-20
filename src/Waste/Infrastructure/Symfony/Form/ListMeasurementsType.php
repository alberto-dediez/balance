<?php

namespace App\Waste\Infrastructure\Symfony\Form;

use Symfony\Component\Form\FormBuilderInterface;
use App\Waste\Infrastructure\Symfony\Type\PurposeType;
use App\Waste\Infrastructure\Symfony\Type\WasteIdType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use App\Waste\Infrastructure\Symfony\Type\PropertyIdType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use App\Waste\Infrastructure\Symfony\Type\ContainerIdType;
use App\Waste\Infrastructure\Symfony\Type\WasteManagerIdType;
use App\Waste\Infrastructure\Symfony\Type\MeasurementMethodType;
use App\Waste\Application\Measurement\List\ListMeasurementsRequest;

class ListMeasurementsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('propertyId', PropertyIdType::class, [
                'label' => new TranslatableMessage('Propiedad'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas las propiedades')
            ])
            ->add('wasteId', WasteIdType::class, [
                'label' => new TranslatableMessage('Tipología de residuo'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas las tipologías')
            ])
            ->add('purpose', PurposeType::class, [
                'label' => new TranslatableMessage('Propósito'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas los propósitos')
            ])
            ->add('measurementMethod', MeasurementMethodType::class, [
                'label' => new TranslatableMessage('Método de medida'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos los métodos')
            ])
            ->add('wasteManagerId', WasteManagerIdType::class, [
                'label' => new TranslatableMessage('Gestor de residuos'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos los gestores')
            ])
        ;
        
        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ListMeasurementsRequest::class
        ]);
    }
}