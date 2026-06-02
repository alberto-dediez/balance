<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Alert;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;
use App\Waste\Domain\Model\Waste\WasteId;

class AlertFilter
{
    use Filter;

    public function __construct(
        public ?AccountId $accountId = null,
        public ?PropertyId $propertyId = null,
        public ?WasteId $wasteId = null,
        public ?AlertType $type = null,
        public ?AlertStatus $status = null,
        public ?\DateTimeImmutable $from = null,
        public ?\DateTimeImmutable $to = null,
        int $limit = 50,
        string $orderBy = 'detectedAt',
        string $orderWay = 'DESC',
    ) {
        $this->limit = $limit;
        $this->orderBy = $orderBy;
        $this->orderWay = $orderWay;
    }
}
