<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Vat\InMemory;

use App\Payments\Domain\Model\Vat\VatCountry;
use App\Payments\Domain\Model\Vat\VatCountryProvider;

final class InMemoryVatCountryProvider implements VatCountryProvider
{
    /** @var array<string, VatCountry> */
    private array $countries = [];

    /** @param VatCountry[] $countries */
    public function __construct(array $countries = [])
    {
        foreach ($countries as $country) {
            $this->add($country);
        }
    }

    public function add(VatCountry $country): void
    {
        $this->countries[strtoupper(trim($country->iso))] = $country;
    }

    public function findByIso(string $iso): ?VatCountry
    {
        return $this->countries[strtoupper(trim($iso))] ?? null;
    }
}
