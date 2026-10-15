<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Catalog;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Process\ProcessFilter;
use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;

final class ProcessCatalogProvider implements ActivityCatalogProvider
{
    public function __construct(private ProcessRepository $processRepository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Process;
    }

    public function catalog(int $year, ?EmissionFactorGroupId $emissionFactorGroupId): array
    {
        if (null === $emissionFactorGroupId) {
            return [];
        }

        return array_values(array_filter(
            $this->processRepository->findByFilter(new ProcessFilter(limit: 0)),
            static fn($item) => null !== $item->emissionFactorFor($year, $emissionFactorGroupId),
        ));
    }

    public function secondaryCatalog(int $year, ?EmissionFactorGroupId $emissionFactorGroupId): array
    {
        return [];
    }
}
