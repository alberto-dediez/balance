<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\ElectricityMix\Update\UpdateElectricityMixRequest;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\ElectricityMixEmissionFactorType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\GroupedEmissionFactorsType;
use App\Property\Infrastructure\Symfony\Form\Type\CountryIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class UpdateElectricityMixType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('countryId', CountryIdType::class, [
                'label' => new TranslatableMessage('País'),
                'required' => true,
                'placeholder' => new TranslatableMessage('Selecciona un país'),
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ])
            ->add('emissionFactors', GroupedEmissionFactorsType::class, [
                'label' => new TranslatableMessage('Factores de emisión'),
                'entry_type' => ElectricityMixEmissionFactorType::class,
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
            'data_class' => UpdateElectricityMixRequest::class,
        ]);
    }
}
