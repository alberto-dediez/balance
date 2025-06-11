<?php

namespace App\Waste\Domain\Model\Measurement;

use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Measurement\MeasurementFilter;

interface MeasurementRepository
{
    public function add(Measurement $measurement): void;

    public function remove(Measurement $measurement): void;

    public function find(MeasurementId $measurementId): ?Measurement;

    public function findByFilter(MeasurementFilter $filter): array;

    public function totalResultsByFilter(MeasurementFilter $filter): int;
}