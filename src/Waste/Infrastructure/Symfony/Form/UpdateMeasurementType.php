<?php

namespace App\Waste\Infrastructure\Symfony\Form;

use App\Shared\Infrastructure\Symfony\Form\Type\NumberUnitType;
use App\Waste\Domain\Model\Measurement\Unit;
use BcMath\Number;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Infrastructure\Symfony\Form\Type\WeightType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Waste\Application\Measurement\Update\UpdateMeasurementRequest;

class UpdateMeasurementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $unit = $options['unit'];

        $builder
            ->add('quantity', IntegerType::class, [
                'label' => new TranslatableMessage('Cantidad de contenedores'),
                'required' => true
            ])
        ;

        if ($unit === Unit::Percentage) {
            $builder->add('load', PercentType::class , [
                'label' => new TranslatableMessage('Volumen del contenedor (%)'),
                'required' => true,
                'type' => 'integer'
            ]);
        } elseif ($unit === Unit::Kilogram) {
            $builder->add('load', WeightType::class , [
                'label' => new TranslatableMessage('Peso del contenedor (Kg)'),
                'required' => true
            ]);
        } else {
            $builder->add('load', NumberUnitType::class , [
                'label' => new TranslatableMessage('Carga del contenedor ({unit})', ['{unit}' => $unit->value]),
                'required' => true,
                'symbol' => $unit->value
            ]);
        }

        $builder
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
            'data_class' => UpdateMeasurementRequest::class,
            'unit' => Unit::Percentage
        ]);
    }
}
