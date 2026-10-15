<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;

final class ProcessEmissionFactorSelector implements EmissionFactorSelector
{
    public function __construct(private ProcessRepository $repository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Process;
    }

    public function select(string $itemId, int $year, ?ItemCondition $condition, EmissionFactorGroupId $groupId, ?string $secondaryItemId = null): EmissionFactor
    {
        $id = new ProcessId($itemId);
        $process = $this->repository->find($id);
        if (null === $process) {
            throw new \InvalidArgumentException(sprintf('Process "%s" not found.', $id));
        }
        $factor = $process->emissionFactorFor($year, $groupId);
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for process "%s" in year %d.', $id, $year));
        }

        return $factor->emissionFactor();
    }
}
