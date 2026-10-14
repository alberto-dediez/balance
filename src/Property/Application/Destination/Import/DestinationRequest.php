<?php

declare(strict_types=1);

namespace App\Property\Application\Destination\Import;

use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;

class DestinationRequest
{
    public function __construct(
        public string $name,
        public CountryId $countryId,
        public ExternalSource $source,
        public ExternalId $externalId,
        public ?bool $createIfNotExists = false,
    ) {}
}
