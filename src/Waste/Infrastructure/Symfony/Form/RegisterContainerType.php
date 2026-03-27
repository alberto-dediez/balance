<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form;

use App\Waste\Infrastructure\Symfony\Form\Type\ContainerVolumeType;
use App\Waste\Infrastructure\Symfony\Form\Type\MeasurementMethodType;
use App\Waste\Infrastructure\Symfony\Form\Type\PropertyIdType;
use App\Waste\Infrastructure\Symfony\Form\Type\PurposeType;
use App\Waste\Infrastructure\Symfony\Form\Type\WasteIdType;
use App\Waste\Infrastructure\Symfony\Form\Type\WasteManagerIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<array<string, mixed>> */
class RegisterContainerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('propertyId', PropertyIdType::class, [
                'label' => new TranslatableMessage('Propiedad'),
                'required' => true,
            ])
            ->add('wasteId', WasteIdType::class, [
                'label' => new TranslatableMessage('Tipología de residuo'),
                'required' => true,
            ])
            ->add('purpose', PurposeType::class, [
                'label' => new TranslatableMessage('Destino'),
                'required' => true,
            ])
            ->add('measurementMethod', MeasurementMethodType::class, [
                'label' => new TranslatableMessage('Método de medida'),
                'required' => true,
            ])
            ->add('volume', ContainerVolumeType::class)
            ->add('wasteManagerId', WasteManagerIdType::class, [
                'label' => new TranslatableMessage('Gestor de residuos'),
                'required' => true,
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([

        ]);
    }
}
