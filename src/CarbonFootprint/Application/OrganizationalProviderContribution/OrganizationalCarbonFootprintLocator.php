<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OrganizationalProviderContribution;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;

final class OrganizationalCarbonFootprintLocator
{
    public function __construct(
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private PropertyCarbonFootprintAuthorizer $propertyCarbonFootprintAuthorizer,
        private CompanyCarbonFootprintAuthorizer $companyCarbonFootprintAuthorizer,
    ) {}

    public function locate(CarbonFootprintReportSubjectType $subjectType, string $carbonFootprintId): LocatedOrganizationalCarbonFootprint
    {
        $located = match ($subjectType) {
            CarbonFootprintReportSubjectType::Property => $this->locateProperty($carbonFootprintId),
            CarbonFootprintReportSubjectType::Company => $this->locateCompany($carbonFootprintId),
            CarbonFootprintReportSubjectType::Event => throw new \InvalidArgumentException('Provider contributions of organizational footprints are not available for events'),
        };

        if (null === $located) {
            throw new \InvalidArgumentException('CarbonFootprint not found');
        }

        return $located;
    }

    public function save(LocatedOrganizationalCarbonFootprint $located): void
    {
        $carbonFootprint = $located->carbonFootprint;

        if ($carbonFootprint instanceof PropertyCarbonFootprint) {
            $this->propertyCarbonFootprintRepository->add($carbonFootprint);

            return;
        }

        $this->companyCarbonFootprintRepository->add($carbonFootprint);
    }

    private function locateProperty(string $carbonFootprintId): ?LocatedOrganizationalCarbonFootprint
    {
        $carbonFootprint = $this->propertyCarbonFootprintRepository->find(new PropertyCarbonFootprintId($carbonFootprintId));

        if (!$carbonFootprint instanceof PropertyCarbonFootprint || $carbonFootprint->isRemoved()) {
            return null;
        }

        return new LocatedOrganizationalCarbonFootprint($carbonFootprint, CarbonFootprintReportSubjectType::Property, $this->propertyCarbonFootprintAuthorizer);
    }

    private function locateCompany(string $carbonFootprintId): ?LocatedOrganizationalCarbonFootprint
    {
        $carbonFootprint = $this->companyCarbonFootprintRepository->find(new CompanyCarbonFootprintId($carbonFootprintId));

        if (!$carbonFootprint instanceof CompanyCarbonFootprint || $carbonFootprint->isRemoved()) {
            return null;
        }

        return new LocatedOrganizationalCarbonFootprint($carbonFootprint, CarbonFootprintReportSubjectType::Company, $this->companyCarbonFootprintAuthorizer);
    }
}
