<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\Mobility\Update\UpdateMobilityRequest;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\FactorBasisType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\GroupedEmissionFactorsType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\MobilityEmissionFactorType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\TravelModeType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\UnitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class UpdateMobilityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('unit', UnitType::class, [
                'label' => new TranslatableMessage('Unidad'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('travelMode', TravelModeType::class, [
                'label' => new TranslatableMessage('Modo de cálculo de rutas'),
                'help' => new TranslatableMessage('Si se deja vacío no se calcularán distancias automáticas para este medio de transporte.'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Sin cálculo automático'),
            ])
            ->add('factorBasis', FactorBasisType::class, [
                'label' => new TranslatableMessage('Base del factor de emisión'),
                'help' => new TranslatableMessage('Por pasajero: el factor ya es por persona y los ocupantes no reducen las emisiones. Por vehículo: el factor es del vehículo completo y se reparte entre sus ocupantes.'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('emissionFactors', GroupedEmissionFactorsType::class, [
                'label' => new TranslatableMessage('Factores de emisión'),
                'entry_type' => MobilityEmissionFactorType::class,
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir'),
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateMobilityRequest::class,
        ]);
    }
}
