<?php

declare(strict_types=1);

namespace App\Property\Application\Country\Import;

use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;

class CountryRequest
{
    public function __construct(
        public string $name,
        public ExternalSource $source,
        public ExternalId $externalId,
        public ?string $iso = null,
        public ?bool $createIfNotExists = false,
        public ?float $vatRate = null,
        public bool $europeanUnion = false,
    ) {}
}
