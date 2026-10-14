<?php

declare(strict_types=1);

namespace App\Property\Application\Destination\Import;

use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Property\ExternalId;

class DestinationResponse
{
    public function __construct(
        public string $name,
        public CountryId $countryId,
        public ?ExternalId $externalId = null,
        public ?bool $createIfNotExists = false,
        public ?DestinationId $destinationId = null,
    ) {}
}
