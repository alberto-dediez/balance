<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Contest;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class ContestFilter
{
    use Filter;

    public function __construct(
        public ?AccountId $accountId = null,
        public ?ContestStatus $status = null,
        public ?ContestParticipationMode $participationMode = null,
        public ?ContestAwardMode $awardMode = null,
        public ?string $text = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
