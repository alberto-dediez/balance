<?php

namespace App\Waste\Domain\Model\Waste;

use App\Shared\Domain\Model\Filter;

class WasteFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?bool $isDangerous = null,
        int $limit = 10
    ) {
        $this->limit = $limit;
    }
}