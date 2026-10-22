<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint;

use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyRepository;

class CompanyProperties
{
    public function __construct(private PropertyRepository $propertyRepository) {}

    /** @return list<Property> */
    public function all(?CompanyId $companyId): array
    {
        if (null === $companyId) {
            return [];
        }

        $properties = array_values($this->propertyRepository->findByCompanyId($companyId));

        usort($properties, static fn(Property $a, Property $b): int => strcasecmp($a->name(), $b->name()));

        return $properties;
    }

    /** @return list<Property> */
    public function consolidated(?CompanyId $companyId): array
    {
        return array_values(array_filter(
            $this->all($companyId),
            static fn(Property $property): bool => $property->operatingRegime()->isConsolidated(),
        ));
    }

    /** @return list<Property> */
    public function nonConsolidated(?CompanyId $companyId): array
    {
        return array_values(array_filter(
            $this->all($companyId),
            static fn(Property $property): bool => !$property->operatingRegime()->isConsolidated(),
        ));
    }
}
