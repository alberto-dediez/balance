<?php

namespace App\Waste\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Infrastructure\Symfony\Form\Type\WeightType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Waste\Application\Measurement\Register\RegisterMeasurementRequest;

class RegisterMeasurementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $measurementMethod = $options['measurementMethod'];

        $builder
            ->add('quantity', IntegerType::class, [
                'label' => new TranslatableMessage('Cantidad de contenedores'),
                'required' => true
            ])
        ;

        if ($measurementMethod == MeasurementMethod::Volume) {
            $builder->add('load', PercentType::class , [
                'label' => new TranslatableMessage('Volumen del contenedor (%)'),
                'required' => true,
                'type' => 'integer'
            ]);
        } else {
            $builder->add('load', WeightType::class , [
                'label' => new TranslatableMessage('Peso del contenedor (Kg)'),
                'required' => true
            ]);
        }
        
        $builder
            ->add('justification', TextareaType::class, [
                'label' => new TranslatableMessage('Justificación'),
                'required' => false
            ])
            ->add('register', SubmitType::class, [
                'label' => new TranslatableMessage('Registrar')
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => RegisterMeasurementRequest::class,
            'measurementMethod' => MeasurementMethod::Volume
        ]);
    }
}