<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Country;

use App\Property\Domain\Model\Country\Country;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Country\CountryRepository;
use Symfony\Component\Translation\TranslatableMessage;

trait GetCountryFromIdTrait
{
    private function getCountryFromId(string $countryId, CountryRepository $countryRepository): Country
    {
        $country = $countryRepository->find(new CountryId($countryId));

        if (null == $country) {
            throw $this->createNotFoundException(new TranslatableMessage('El país no existe'));
        }

        return $country;
    }
}
