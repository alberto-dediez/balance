<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Alert;

interface AlertRepository
{
    public function add(Alert $alert): void;

    /**
     * @param array<Alert> $alerts
     */
    public function addMultiple(array $alerts): void;

    public function find(AlertId $alertId): ?Alert;

    /** @return array<Alert> */
    public function findByFilter(AlertFilter $filter): array;

    public function totalResultsByFilter(AlertFilter $filter): int;
}
