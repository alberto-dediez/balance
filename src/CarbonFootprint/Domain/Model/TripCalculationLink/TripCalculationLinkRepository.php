<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculationLink;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;

interface TripCalculationLinkRepository
{
    public function add(TripCalculationLink $tripCalculationLink): void;

    public function find(TripCalculationLinkId $tripCalculationLinkId): ?TripCalculationLink;

    public function findByPropertyId(PropertyId $propertyId): ?TripCalculationLink;

    public function findForAccount(AccountId $accountId): ?TripCalculationLink;

    /** @return list<TripCalculationLink> */
    public function findByFilter(TripCalculationLinkFilter $filter): array;

    public function totalResultsByFilter(TripCalculationLinkFilter $filter): int;

    public function findByToken(#[\SensitiveParameter] string $token): ?TripCalculationLink;
}
