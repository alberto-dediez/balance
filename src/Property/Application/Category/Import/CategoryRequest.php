<?php

declare(strict_types=1);

namespace App\Property\Application\Category\Import;

use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;

class CategoryRequest
{
    public function __construct(
        public string $name,
        public ExternalSource $source,
        public ExternalId $externalId,
        public ?bool $createIfNotExists = false,
    ) {}
}
