<?php

namespace App\Waste\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use App\Waste\Application\Measurement\Update\UpdateMeasurementRequest;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class UpdateMeasurementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantity', IntegerType::class, [
                'label' => new TranslatableMessage('Cantidad de contenedores'),
                'required' => true
            ])
            ->add('load', NumberType::class, [
                'label' => new TranslatableMessage('Carga del contenedor'),
                'required' => true
            ])
            ->add('justification', TextareaType::class, [
                'label' => new TranslatableMessage('Justificación'),
                'required' => false
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
            'data_class' => UpdateMeasurementRequest::class
        ]);
    }
}