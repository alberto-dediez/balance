<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;

final class WasteContainerQuantityResolver
{
    public function __construct(private readonly WasteRepository $repository) {}

    public function resolve(WasteId $wasteId, Quantity $containerCapacity, float $fillPercentage): Quantity
    {
        $waste = $this->repository->find($wasteId)
            ?? throw new \InvalidArgumentException(sprintf('Waste "%s" not found.', $wasteId));

        return $waste->quantityFromContainer($containerCapacity, $fillPercentage);
    }
}
