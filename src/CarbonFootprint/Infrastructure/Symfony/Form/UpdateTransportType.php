<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\Transport\Update\UpdateTransportRequest;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\GroupedEmissionFactorsType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\TransportEmissionFactorType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\TransportVehicleType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\UnitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class UpdateTransportType extends AbstractType
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
            ->add('vehicle', TransportVehicleType::class, [
                'label' => new TranslatableMessage('Tipo de vehículo o medio'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('emissionFactors', GroupedEmissionFactorsType::class, [
                'label' => new TranslatableMessage('Factores de emisión'),
                'entry_type' => TransportEmissionFactorType::class,
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
            'data_class' => UpdateTransportRequest::class,
        ]);
    }
}
