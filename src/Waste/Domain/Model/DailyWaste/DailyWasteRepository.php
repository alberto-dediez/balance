<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\DailyWaste;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

interface DailyWasteRepository
{
    public function add(DailyWaste $dailyWaste): void;

    public function remove(DailyWaste $dailyWaste): void;

    public function find(DailyWasteId $dailyWasteId): ?DailyWaste;

    public function findByDay(\DateTimeImmutable $day, PropertyId $propertyId, WasteId $wasteId, Purpose $purpose, WasteManagerId $wasteManagerId, AccountId $accountId): ?DailyWaste;

    public function findByFilter(DailyWasteFilter $filter): array;

    public function totalResultsByFilter(DailyWasteFilter $filter): int;
}
