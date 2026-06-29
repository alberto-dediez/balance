<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Event;

use App\Property\Domain\Model\Destination\DestinationId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class EventFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?AccountId $accountId = null,
        public ?DestinationId $location = null,
        public ?EventSize $size = null,
        public ?EventSector $sector = null,
        public ?EventTypology $typology = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
