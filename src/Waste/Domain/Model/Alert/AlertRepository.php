<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Alert;

interface AlertRepository
{
    public function add(Alert $alert): void;

    public function save(Alert $alert): void;

    public function find(AlertId $alertId): ?Alert;

    /** @return array<Alert> */
    public function findByFilter(AlertFilter $filter): array;

    public function totalResultsByFilter(AlertFilter $filter): int;
}
