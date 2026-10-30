<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OperatorContribution;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;

interface OperatorContributionRepository
{
    public function add(OperatorContribution $contribution): void;

    public function find(OperatorContributionId $id): ?OperatorContribution;

    public function findByToken(#[\SensitiveParameter] string $token): ?OperatorContribution;

    /** @return list<OperatorContribution> */
    public function findActiveByCompanyCarbonFootprint(CompanyCarbonFootprintId $id): array;

    public function findActiveByCompanyCarbonFootprintAndProperty(CompanyCarbonFootprintId $id, PropertyId $propertyId): ?OperatorContribution;

    public function findLatestByAccountAndProperty(AccountId $accountId, PropertyId $propertyId): ?OperatorContribution;

    /** @return list<OperatorContribution> */
    public function findByFilter(OperatorContributionFilter $filter): array;

    public function totalResultsByFilter(OperatorContributionFilter $filter): int;
}
