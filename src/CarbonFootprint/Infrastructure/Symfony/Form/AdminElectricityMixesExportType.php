<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\ElectricityMix\Export\ElectricityMixesExportFilter;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\EmissionFactorGroupChoiceType;
use App\Property\Infrastructure\Symfony\Form\Type\CountryIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<ElectricityMixesExportFilter> */
class AdminElectricityMixesExportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('countryId', CountryIdType::class, [
                'label' => new TranslatableMessage('País'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
            ])
            ->add('emissionFactorGroup', EmissionFactorGroupChoiceType::class, [
                'label' => new TranslatableMessage('Grupo'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ElectricityMixesExportFilter::class,
        ]);
    }
}
