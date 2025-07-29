<?php

namespace App\Property\Application\Brand\Import;

use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;

class BrandRequest
{
    public function __construct(
        public string $name,
        public ExternalSource $source,
        public ExternalId $externalId,
        public ?bool $createIfNotExists = false
    ) {

    }
}