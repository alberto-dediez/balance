<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\List;

class ListAlertsResponse
{
    /** @param array<\App\Waste\Domain\Model\Alert\Alert> $alerts */
    public function __construct(public array $alerts = [], public int $totalResults = 0) {}
}
