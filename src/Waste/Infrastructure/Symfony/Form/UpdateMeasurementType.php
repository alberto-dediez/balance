<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form;

use App\Shared\Infrastructure\Symfony\Form\Type\NumberUnitType;
use App\Waste\Application\Measurement\Update\UpdateMeasurementRequest;
use App\Waste\Domain\Model\Measurement\Unit;
use App\Waste\Infrastructure\Symfony\Form\Type\WeightType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<array<string, mixed>> */
class UpdateMeasurementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $unit = $options['unit'];

        $builder
            ->add('quantity', IntegerType::class, [
                'label' => new TranslatableMessage('Cantidad de contenedores'),
                'required' => true,
            ])
        ;

        if (Unit::Percentage === $unit) {
            $builder->add('load', PercentType::class, [
                'label' => new TranslatableMessage('Capacidad del contenedor (%)'),
                'required' => true,
                'type' => 'integer',
            ]);
        } elseif (Unit::Kilogram === $unit) {
            $builder->add('load', WeightType::class, [
                'label' => new TranslatableMessage('Peso del contenedor (Kg)'),
                'required' => true,
            ]);
        } else {
            $builder->add('load', NumberUnitType::class, [
                'label' => new TranslatableMessage('Carga del contenedor ({unit})', ['{unit}' => $unit->value]),
                'required' => true,
                'symbol' => $unit->value,
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
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar'),
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateMeasurementRequest::class,
            'unit' => Unit::Percentage,
        ]);
    }
}
