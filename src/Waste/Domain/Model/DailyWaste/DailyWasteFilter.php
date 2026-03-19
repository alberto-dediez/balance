<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\DailyWaste;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

class DailyWasteFilter
{
    use Filter;

    public function __construct(
        public array $properties = [],
        public ?WasteId $wasteId = null,
        public ?WasteManagerId $wasteManagerId = null,
        public ?Purpose $purpose = null,
        public ?AccountId $accountId = null,
        public ?\DateTimeImmutable $day = null,
        public ?\DateTimeImmutable $dayFrom = null,
        public ?\DateTimeImmutable $dayTo = null,
        int $limit = 10,
        string $orderBy = 'default',
        string $orderWay = 'DESC',
    ) {
        $this->limit = $limit;
        $this->orderBy = $orderBy;
        $this->orderWay = $orderWay;
    }
}
