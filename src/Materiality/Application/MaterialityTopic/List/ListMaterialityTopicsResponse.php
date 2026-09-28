<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialityTopic\List;

use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;

class ListMaterialityTopicsResponse
{
    /** @param array<MaterialityTopic> $materialityTopics */
    public function __construct(
        public array $materialityTopics = [],
        public int $totalResults = 0,
    ) {}
}
