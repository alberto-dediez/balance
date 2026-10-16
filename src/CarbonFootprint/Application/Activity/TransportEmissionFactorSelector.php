<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;

final class TransportEmissionFactorSelector implements EmissionFactorSelector
{
    public function __construct(private TransportRepository $repository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Transport;
    }

    public function select(string $itemId, int $year, ?ItemCondition $condition, EmissionFactorGroupId $groupId, ?string $secondaryItemId = null): EmissionFactor
    {
        $id = new TransportId($itemId);
        $transport = $this->repository->find($id);
        if (null === $transport) {
            throw new \InvalidArgumentException(sprintf('Transport "%s" not found.', $id));
        }
        $factor = $transport->emissionFactorFor($year, $groupId);
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for transport "%s" in year %d.', $id, $year));
        }

        return $factor;
    }
}
