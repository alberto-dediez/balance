<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintKpi;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;

class KpiSubjectResolver
{
    public function __construct(
        private PropertyRepository $propertyRepository,
        private CompanyRepository $companyRepository,
    ) {}

    public function resolve(CarbonFootprintReportSubjectType $type, string $id): ?KpiSubject
    {
        if ('' === $id) {
            return null;
        }

        return match ($type) {
            CarbonFootprintReportSubjectType::Property => $this->resolveProperty($id),
            CarbonFootprintReportSubjectType::Company => $this->resolveCompany($id),
            CarbonFootprintReportSubjectType::Event => null,
        };
    }

    public static function segmentKeyOf(Property $property): string
    {
        return $property->info()->propertyType()->value ?? KpiSubject::OTHER_SEGMENT;
    }

    private function resolveProperty(string $id): ?KpiSubject
    {
        $property = $this->propertyRepository->find(new PropertyId($id));

        if (null === $property || $property->isRemoved()) {
            return null;
        }

        return new KpiSubject(
            type: CarbonFootprintReportSubjectType::Property,
            id: (string) $property->id(),
            accountId: $property->accountId(),
            name: $property->name(),
            segmentKey: self::segmentKeyOf($property),
        );
    }

    private function resolveCompany(string $id): ?KpiSubject
    {
        $company = $this->companyRepository->find(new CompanyId($id));

        if (null === $company || $company->isRemoved()) {
            return null;
        }

        return new KpiSubject(
            type: CarbonFootprintReportSubjectType::Company,
            id: (string) $company->id(),
            accountId: $company->accountId(),
            name: $company->name(),
            segmentKey: KpiSubject::COMPANY_SEGMENT,
        );
    }
}
