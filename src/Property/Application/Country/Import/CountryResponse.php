<?php

declare(strict_types=1);

namespace App\Property\Application\Country\Import;

use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\ExternalId;

class CountryResponse
{
    public function __construct(
        public string $name,
        public ?string $iso = null,
        public ?ExternalId $externalId = null,
        public ?bool $createIfNotExists = false,
        public ?CountryId $countryId = null,
        public ?float $vatRate = null,
        public bool $europeanUnion = false,
    ) {}
}
