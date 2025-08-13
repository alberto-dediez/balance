<?php

namespace App\Waste\Domain\Model\Measurement;

use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Container\Purpose;
use App\Security\Domain\Model\Account\AccountId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Waste\Domain\Model\Measurement\MeasurementFilter;

interface MeasurementRepository
{
    public function add(Measurement $measurement): void;

    public function remove(Measurement $measurement): void;

    public function find(MeasurementId $measurementId): ?Measurement;

    public function findByDay(\DateTimeImmutable $day, PropertyId $propertyId, WasteId $wasteId, Purpose $purpose, WasteManagerId $wasteManagerId, AccountId $accountId): array;

    public function findByFilter(MeasurementFilter $filter): array;

    public function totalResultsByFilter(MeasurementFilter $filter): int;
}