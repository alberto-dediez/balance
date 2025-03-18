<?php

namespace App\Waste\Domain\Model\Measurement;

use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Measurement\MeasurementFilter;

interface MeasurementRepository
{
    public function add(Measurement $container): void;

    public function remove(Measurement $container): void;

    public function find(MeasurementId $containerId): ?Measurement;

    public function findByFilter(MeasurementFilter $filter): array;

    public function totalResultsByFilter(MeasurementFilter $filter): int;
}