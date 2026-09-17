<?php

declare(strict_types=1);

namespace App\Bioscore\Domain\Model\BioscoreCatalogItem;

use App\Shared\Domain\Model\Filter;

class BioscoreCatalogItemFilter
{
    use Filter;

    public function __construct(
        public ?BioscoreCatalog $catalog = null,
        public ?BioscoreMappingStatus $status = null,
        public ?bool $withdrawn = null,
        public ?string $name = null,
        public ?string $targetId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
