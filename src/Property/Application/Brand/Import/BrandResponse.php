<?php

namespace App\Property\Application\Brand\Import;

use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Domain\Model\Property\ExternalId;

class BrandResponse
{
    public function __construct(
        public string $name,
        public ?ExternalId $externalId = null,
        public ?bool $createIfNotExists = false,
        public ?BrandId $brandId = null
    ) {

    }
}