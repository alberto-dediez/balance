<?php

namespace App\Waste\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use App\Waste\Infrastructure\Symfony\Type\PurposeType;
use App\Waste\Infrastructure\Symfony\Type\WasteIdType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Waste\Infrastructure\Symfony\Type\WasteManagerIdType;
use App\Waste\Infrastructure\Symfony\Type\MeasurementMethodType;
use App\Waste\Application\Container\Update\UpdateContainerRequest;

class UpdateContainerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('wasteId', WasteIdType::class, [
                'label' => new TranslatableMessage('Tipología de residuo'),
                'required' => true
            ])
            ->add('purpose', PurposeType::class, [
                'label' => new TranslatableMessage('Propósito'),
                'required' => true
            ])
            ->add('measurementMethod', MeasurementMethodType::class, [
                'label' => new TranslatableMessage('Método de medida'),
                'required' => true
            ])
            ->add('volume', NumberType::class, [
                'label' => new TranslatableMessage('Volumen'),
                'required' => false
            ])
            ->add('wasteManagerId', WasteManagerIdType::class, [
                'label' => new TranslatableMessage('Gestor de residuos'),
                'required' => true
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar')
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir')
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UpdateContainerRequest::class
        ]);
    }
}