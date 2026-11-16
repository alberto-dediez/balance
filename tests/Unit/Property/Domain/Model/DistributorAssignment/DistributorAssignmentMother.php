<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Model\DistributorAssignment;

use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignmentId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;

class DistributorAssignmentMother
{
    public static function forAccount(AccountId $distributorAccountId, AccountId $accountId, ?DistributorAssignmentId $id = null): DistributorAssignment
    {
        $assignment = new DistributorAssignment($id ?? DistributorAssignmentId::generate());
        $assignment->registerForAccount($distributorAccountId, $accountId, UserId::generate());

        return $assignment;
    }

    public static function forProperty(AccountId $distributorAccountId, PropertyId $propertyId, ?DistributorAssignmentId $id = null): DistributorAssignment
    {
        $assignment = new DistributorAssignment($id ?? DistributorAssignmentId::generate());
        $assignment->registerForProperty($distributorAccountId, $propertyId, UserId::generate());

        return $assignment;
    }

    public static function forAccountRegisteredAt(AccountId $distributorAccountId, AccountId $accountId, \DateTimeImmutable $registeredAt): DistributorAssignment
    {
        return new DistributorAssignment(
            id: DistributorAssignmentId::generate(),
            distributorAccountId: $distributorAccountId,
            accountId: $accountId,
            registeredBy: UserId::generate(),
            registeredAt: $registeredAt,
        );
    }

    public static function forPropertyRegisteredAt(AccountId $distributorAccountId, PropertyId $propertyId, \DateTimeImmutable $registeredAt): DistributorAssignment
    {
        return new DistributorAssignment(
            id: DistributorAssignmentId::generate(),
            distributorAccountId: $distributorAccountId,
            propertyId: $propertyId,
            registeredBy: UserId::generate(),
            registeredAt: $registeredAt,
        );
    }
}
