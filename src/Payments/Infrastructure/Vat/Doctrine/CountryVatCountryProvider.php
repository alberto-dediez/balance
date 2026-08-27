<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Vat\Doctrine;

use App\Payments\Domain\Model\Vat\VatCountry;
use App\Payments\Domain\Model\Vat\VatCountryProvider;
use App\Property\Domain\Model\Country\CountryRepository;

final class CountryVatCountryProvider implements VatCountryProvider
{
    public function __construct(private CountryRepository $countryRepository) {}

    public function findByIso(string $iso): ?VatCountry
    {
        $iso = strtoupper(trim($iso));

        if ('' === $iso) {
            return null;
        }

        $country = $this->countryRepository->findByIso($iso);

        if (null === $country || $country->isRemoved()) {
            return null;
        }

        return new VatCountry(
            iso: $iso,
            rate: $country->vatRate() ?? 0.0,
            europeanUnion: $country->isEuropeanUnion(),
        );
    }
}
