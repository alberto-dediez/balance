<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\DistributorAssignment;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;

interface DistributorAssignmentRepository
{
    public function add(DistributorAssignment $assignment): void;

    public function remove(DistributorAssignment $assignment): void;

    public function find(DistributorAssignmentId $id): ?DistributorAssignment;

    public function findForAccount(AccountId $distributorAccountId, AccountId $accountId): ?DistributorAssignment;

    public function findForProperty(AccountId $distributorAccountId, PropertyId $propertyId): ?DistributorAssignment;

    /** @return list<DistributorAssignment> */
    public function findByFilter(DistributorAssignmentFilter $filter): array;

    public function totalResultsByFilter(DistributorAssignmentFilter $filter): int;
}
