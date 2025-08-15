<?php

namespace App\Security\Domain\Model\RecordedEvent;

use App\Shared\Domain\Model\Filter;
use App\Security\Domain\Model\User\UserId;

class RecordedEventFilter
{
    use Filter;
    
    public function __construct(
        public ?UserId $userId = null,
        public ?string $eventName = null,
        public ?string $context = null,
        public ?string $aggregateRootId = null,
        int $limit = 10
    ) {
        $this->limit = $limit;
        $this->orderWay = 'DESC';
    }
}