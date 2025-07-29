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
        private CountryRepository $countryRepository
    ) {

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $countries = $this->countryRepository->findByFilter(new CountryFilter(limit: 0));

        $resolver->setDefaults(array(
            'choices' => $this->getChoices($countries),
            'class' => CountryId::class
        ));
    }
}