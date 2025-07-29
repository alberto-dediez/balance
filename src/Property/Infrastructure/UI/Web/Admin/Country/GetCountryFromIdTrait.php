<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Country;

use App\Property\Domain\Model\Country\Country;
use App\Property\Domain\Model\Country\CountryId;
use Symfony\Component\Translation\TranslatableMessage;
use App\Property\Domain\Model\Country\CountryRepository;

trait GetCountryFromIdTrait
{
    private function getCountryFromId(string $countryId, CountryRepository $countryRepository): Country
    {
        $country = $countryRepository->find(new CountryId($countryId));

        if (null == $country) {
            throw $this->createNotFoundException(new TranslatableMessage("El país no existe"));
        }

        return $country;
    }
}