<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\ProtocolAccess;

use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccess;
use App\FoodLoss\Domain\Model\ProtocolAccess\ProtocolAccessId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Domain\Model\Account\AccountId;

class ProtocolAccessMother
{
    public static function registered(): ProtocolAccess
    {
        return self::forProtocolAndEmployee(
            protocolId: (string) ProtocolId::generate(),
            employeeId: (string) EmployeeId::generate(),
            accountId: 'account-id',
        );
    }

    public static function forProtocolAndEmployee(string $protocolId, string $employeeId, string $accountId = 'account-id'): ProtocolAccess
    {
        $protocolAccess = new ProtocolAccess(id: ProtocolAccessId::generate());
        $protocolAccess->register(
            protocolId: new ProtocolId($protocolId),
            employeeId: new EmployeeId($employeeId),
            accountId: new AccountId($accountId),
        );

        return $protocolAccess;
    }

    public static function forProtocolAndEmployeeAt(string $protocolId, string $employeeId, \DateTimeImmutable $accessedAt, string $accountId = 'account-id'): ProtocolAccess
    {
        return new ProtocolAccess(
            id: ProtocolAccessId::generate(),
            protocolId: new ProtocolId($protocolId),
            employeeId: new EmployeeId($employeeId),
            accountId: new AccountId($accountId),
            accessedAt: $accessedAt,
        );
    }

    public static function random(): ProtocolAccess
    {
        return self::forProtocolAndEmployee(
            protocolId: (string) ProtocolId::generate(),
            employeeId: (string) EmployeeId::generate(),
        );
    }
}
