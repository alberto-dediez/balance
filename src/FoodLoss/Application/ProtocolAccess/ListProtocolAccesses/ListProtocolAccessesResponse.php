<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\ProtocolAccess\ListProtocolAccesses;

class ListProtocolAccessesResponse
{
    /** @param array<ProtocolAccessSummary> $summaries */
    public function __construct(
        public array $summaries = [],
    ) {}
}
