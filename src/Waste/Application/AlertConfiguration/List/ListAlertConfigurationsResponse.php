<?php

declare(strict_types=1);

namespace App\Waste\Application\AlertConfiguration\List;

class ListAlertConfigurationsResponse
{
    /** @param array<\App\Waste\Domain\Model\AlertConfiguration\AlertConfiguration> $alertConfigurations */
    public function __construct(public array $alertConfigurations = [], public int $totalResults = 0) {}
}
