<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Unit;

final class TransportAnswer extends ActivityAnswer
{
    public function __construct(
        public readonly TransportId $transportId,
        Quantity $quantity,
        ?ActivityId $existingActivityId = null,
        ?string $source = null,
        public readonly ?Quantity $weight = null,
        public readonly ?float $distanceKm = null,
    ) {
        parent::__construct($quantity, $existingActivityId, $source);
    }

    public static function fromWeightAndDistance(
        TransportId $transportId,
        Quantity $weight,
        float $distanceKm,
        ?ActivityId $existingActivityId = null,
        ?string $source = null,
    ): self {
        return new self(
            transportId: $transportId,
            quantity: new Quantity($weight->convertTo(Unit::Tonne)->value() * $distanceKm, Unit::TonneKilometre),
            existingActivityId: $existingActivityId,
            source: $source,
            weight: $weight,
            distanceKm: $distanceKm,
        );
    }

    public function type(): ActivityType
    {
        return ActivityType::Transport;
    }
}
