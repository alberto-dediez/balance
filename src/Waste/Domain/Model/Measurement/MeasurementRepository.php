<?php

namespace App\Waste\Domain\Model\Measurement;

use App\Waste\Domain\Model\DailyWaste\DailyWaste;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Measurement\MeasurementFilter;

interface MeasurementRepository
{
    public function add(Measurement $measurement): void;

    public function remove(Measurement $measurement): void;

    public function find(MeasurementId $measurementId): ?Measurement;

    public function findByDay(DailyWaste $dailyWaste): array;

    public function findByFilter(MeasurementFilter $filter): array;

    public function totalResultsByFilter(MeasurementFilter $filter): int;
}