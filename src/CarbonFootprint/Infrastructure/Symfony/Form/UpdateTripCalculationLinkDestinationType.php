<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationMode;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationPlaceKind;
use App\CarbonFootprint\Infrastructure\UI\Web\AccountTripCalculator\TripCalculationDestinationFormData;
use App\Shared\Domain\Model\Geo\PlaceKind;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use App\Shared\Infrastructure\Symfony\Form\Type\PlaceReferenceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<TripCalculationDestinationFormData> */
class UpdateTripCalculationLinkDestinationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mode', EnumType::class, [
                'label' => new TranslatableMessage('Destinos permitidos'),
                'class' => TripDestinationMode::class,
                'choice_label' => static fn(TripDestinationMode $mode): string => $mode->value,
                'choice_translation_domain' => 'tripDestinationMode',
                'expanded' => true,
                'required' => true,
            ])
            ->add('placeKind', EnumType::class, [
                'label' => new TranslatableMessage('Tipo de destino'),
                'class' => TripDestinationPlaceKind::class,
                'choice_label' => static fn(TripDestinationPlaceKind $placeKind): string => $placeKind->value,
                'choice_translation_domain' => 'tripDestinationPlaceKind',
                'help' => new TranslatableMessage('No aplica cuando el destino es una de tus propiedades.'),
                'required' => true,
            ])
            ->add('countryIds', AutocompleteType::class, [
                'label' => new TranslatableMessage('Países'),
                'choices' => $options['countries'],
                'choice_translation_domain' => false,
                'multiple' => true,
                'required' => false,
                'help' => new TranslatableMessage('Hasta 15 países.'),
            ])
            ->add('regionReference', PlaceReferenceType::class, [
                'label' => new TranslatableMessage('Región'),
                'kind' => PlaceKind::Region,
                'value_label' => $options['region_label'],
                'required' => false,
            ])
            ->add('propertyIds', AutocompleteType::class, [
                'label' => new TranslatableMessage('Propiedades'),
                'choices' => $options['properties'],
                'choice_translation_domain' => false,
                'multiple' => true,
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TripCalculationDestinationFormData::class,
            'countries' => [],
            'properties' => [],
            'region_label' => null,
        ]);

        $resolver->setAllowedTypes('countries', 'array');
        $resolver->setAllowedTypes('properties', 'array');
        $resolver->setAllowedTypes('region_label', ['null', 'string']);
    }
}
