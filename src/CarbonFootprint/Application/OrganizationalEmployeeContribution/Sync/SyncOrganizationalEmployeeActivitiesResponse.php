<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Sync;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\Shared\Application\CommandBus\CommandResponse;

class SyncOrganizationalEmployeeActivitiesResponse implements CommandResponse
{
    /**
     * @param list<Source>       $excludedSources
     * @param list<string>       $skippedMobilities
     * @param array<string, int> $fallbackYears
     */
    public function __construct(
        public int $answeredCount = 0,
        public ?int $totalEmployees = null,
        public ?float $marginOfError = null,
        public bool $overnightStayCatalogMissing = false,
        public array $excludedSources = [],
        public array $skippedMobilities = [],
        public array $fallbackYears = [],
    ) {}
}
