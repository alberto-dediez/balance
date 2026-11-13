<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\Property;
use App\Security\Domain\Model\Account\AccountId;

class CompletedPropertyFootprintFinder
{
    public function __construct(private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository) {}

    public function find(Property $property, CompanyCarbonFootprint $companyCarbonFootprint): ?PropertyCarbonFootprint
    {
        $propertyCarbonFootprint = $this->propertyCarbonFootprintRepository->findByPropertyIdAndYear($property->id(), $companyCarbonFootprint->year());

        if (null === $propertyCarbonFootprint || $propertyCarbonFootprint->isRemoved() || !$propertyCarbonFootprint->isCompleted()) {
            return null;
        }

        $accountId = $propertyCarbonFootprint->accountId();
        $companyAccountId = $companyCarbonFootprint->accountId();

        if (!$accountId instanceof AccountId || !$companyAccountId instanceof AccountId || !$accountId->equals($companyAccountId)) {
            return null;
        }

        return $propertyCarbonFootprint;
    }
}
