<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityTopic;

use App\Shared\Domain\Model\Filter;

class MaterialityTopicFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?MaterialityScope $scope = null,
        public ?MaterialityTopicDirection $direction = null,
        public ?bool $active = null,
        public ?bool $standard = null,
        public bool $includeRemoved = false,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
