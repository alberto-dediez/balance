<?php

namespace App\Security\Domain\Model\RecordedEvent;

use App\Security\Domain\Model\RecordedEvent\RecordedEvent;
use App\Security\Domain\Model\RecordedEvent\RecordedEventId;

interface RecordedEventRepository
{
    public function add(RecordedEvent $account): void;

    public function remove(RecordedEvent $account): void;

    public function find(RecordedEventId $accountId): ?RecordedEvent;

    public function findByFilter(RecordedEventFilter $filter): array;

    public function totalResultsByFilter(RecordedEventFilter $filter): int;
}