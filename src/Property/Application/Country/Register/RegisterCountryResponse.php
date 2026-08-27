<?php

declare(strict_types=1);

namespace App\Property\Application\Country\Register;

use App\Property\Domain\Model\Country\CountryId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterCountryResponse implements CommandResponse
{
    public function __construct(
        public CountryId $countryId,
        public string $name,
        public ?string $iso = null,
        public ?float $vatRate = null,
        public bool $europeanUnion = false,
    ) {}
}
