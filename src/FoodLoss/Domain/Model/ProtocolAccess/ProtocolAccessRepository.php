<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\ProtocolAccess;

use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;

interface ProtocolAccessRepository
{
    public function add(ProtocolAccess $protocolAccess): void;

    public function find(ProtocolAccessId $protocolAccessId): ?ProtocolAccess;

    /** @return array<ProtocolAccess> */
    public function findByFilter(ProtocolAccessFilter $filter): array;

    public function totalResultsByFilter(ProtocolAccessFilter $filter): int;

    /** @return array<ProtocolAccess> Last access per employee for a given protocol */
    public function findLastAccessPerEmployee(ProtocolId $protocolId): array;

    /** @return array<ProtocolAccess> Full history for a given employee and protocol */
    public function findHistoryByProtocolAndEmployee(ProtocolId $protocolId, EmployeeId $employeeId): array;
}
