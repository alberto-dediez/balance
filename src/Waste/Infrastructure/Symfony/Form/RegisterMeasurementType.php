<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form;

use App\Waste\Application\Measurement\Register\RegisterMeasurementRequest;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Infrastructure\Symfony\Form\Type\WeightType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<array<string, mixed>> */
class RegisterMeasurementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $measurementMethod = $options['measurementMethod'];

        $builder
            ->add('quantity', IntegerType::class, [
                'label' => new TranslatableMessage('Cantidad de contenedores'),
                'required' => true,
            ])
        ;

        if (MeasurementMethod::Volume == $measurementMethod) {
            $builder->add('load', PercentType::class, [
                'label' => new TranslatableMessage('Capacidad del contenedor (%)'),
                'required' => true,
                'type' => 'integer',
            ]);
        } else {
            $builder->add('load', WeightType::class, [
                'label' => new TranslatableMessage('Peso del contenedor (Kg)'),
                'required' => true,
            ]);
        }

        $builder
            ->add('justification', TextareaType::class, [
                'label' => new TranslatableMessage('Justificación'),
                'required' => false,
            ])
            ->add('reference', TextType::class, [
                'label' => new TranslatableMessage('Referencia'),
                'required' => false,
            ])
            ->add('day', DateType::class, [
                'label' => new TranslatableMessage('Fecha de registro'),
                'input' => 'datetime_immutable',
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
            'data_class' => RegisterMeasurementRequest::class,
            'measurementMethod' => MeasurementMethod::Volume,
        ]);
    }
}
