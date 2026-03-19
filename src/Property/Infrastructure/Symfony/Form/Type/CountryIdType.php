<?php

namespace App\Property\Infrastructure\Symfony\Form\Type;

use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Country\CountryFilter;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Shared\Infrastructure\Symfony\Form\Type\AggregateRootIdType;

class CountryIdType extends AggregateRootIdType
{
    public function __construct(
        private readonly CountryRepository $countryRepository
    ) {

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $countries = $this->countryRepository->findByFilter(new CountryFilter(limit: 0));

        $resolver->setDefaults([
            'choices' => $this->getChoices($countries),
            'class' => CountryId::class
        ]);
    }
}