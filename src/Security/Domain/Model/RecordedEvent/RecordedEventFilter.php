<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\RecordedEvent;

use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\Filter;

class RecordedEventFilter
{
    use Filter;

    public function __construct(
        public ?UserId $userId = null,
        public ?UserId $impersonatorId = null,
        public ?string $eventName = null,
        public ?string $context = null,
        public ?string $aggregateRootId = null,
        /**
         * @var list<string>|null
         */
        public ?array $allowedEventNames = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
        $this->orderWay = 'DESC';
    }
}
