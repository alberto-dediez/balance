<?php

namespace App\Property\Application\Category\Import;

use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Domain\Model\Property\ExternalId;

class CategoryResponse
{
    public function __construct(
        public string $name,
        public ?ExternalId $externalId = null,
        public ?bool $createIfNotExists = false,
        public ?CategoryId $categoryId = null
    ) {

    }
}