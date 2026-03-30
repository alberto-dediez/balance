<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Measurement;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\Filter;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class MeasurementFilter
{
    use Filter;

    public function __construct(
        public ?ContainerId $containerId = null,
        public ?PropertyId $propertyId = null,
        public ?WasteId $wasteId = null,
        public ?MeasurementMethod $measurementMethod = null,
        public ?WasteManagerId $wasteManagerId = null,
        public ?Purpose $purpose = null,
        public ?AccountId $accountId = null,
        public ?UserId $userId = null,
        public ?\DateTimeImmutable $registeredAt = null,
        public ?string $reference = null,
        int $limit = 10,
        string $orderBy = 'default',
        string $orderWay = 'DESC',
    ) {
        $this->limit = $limit;
        $this->orderBy = $orderBy;
        $this->orderWay = $orderWay;
    }
}
