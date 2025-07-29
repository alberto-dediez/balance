<?php

namespace App\Property\Application\Destination\Import;

use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Destination\DestinationId;

class DestinationResponse
{
    public function __construct(
        public string $name,
        public CountryId $countryId,
        public ?ExternalId $externalId = null,
        public ?bool $createIfNotExists = false,
        public ?DestinationId $destinationId = null
    ) {

    }
}