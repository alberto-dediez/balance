<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixFilter;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class ElectricityMixChoiceType extends AbstractType
{
    public function __construct(
        private readonly ElectricityMixRepository $electricityMixRepository,
        private readonly CountryRepository $countryRepository,
    ) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];

        foreach ($this->electricityMixRepository->findByFilter(new ElectricityMixFilter(limit: 0)) as $electricityMix) {
            $country = $this->countryRepository->find($electricityMix->countryId());

            $choices[null !== $country ? $country->name() : (string) $electricityMix->id()] = (string) $electricityMix->id();
        }

        $resolver->setDefaults([
            'choices' => $choices,
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }
}
