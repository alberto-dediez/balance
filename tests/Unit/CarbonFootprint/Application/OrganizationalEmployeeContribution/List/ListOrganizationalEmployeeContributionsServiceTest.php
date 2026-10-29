<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution\List;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\EmployeeContributionEmissionsCalculator;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\List\ListOrganizationalEmployeeContributionsRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\List\ListOrganizationalEmployeeContributionsService;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Sync\SyncOrganizationalEmployeeActivitiesRequest;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionServiceTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionMother;

class ListOrganizationalEmployeeContributionsServiceTest extends OrganizationalEmployeeContributionServiceTestCase
{
    private ListOrganizationalEmployeeContributionsService $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new ListOrganizationalEmployeeContributionsService(
            $this->locator,
            $this->participationRepository,
            $this->contributionRepository,
            $this->employeeRepository,
            $this->propertyRepository,
            new EmployeeContributionEmissionsCalculator($this->activityRepository),
        );
    }

    public function testReturnsTheCountersTheEmployeesAndTheEmissionsByBlock(): void
    {
        $companyId = CompanyId::generate();
        $hotel = $this->consolidatedProperty($companyId, 'Hotel Mar');
        $footprint = $this->companyFootprint($companyId);
        $this->participation($footprint, CarbonFootprintReportSubjectType::Company, 4);
        $employee = $this->employee(name: 'Ana');
        $train = $this->mobility();
        $this->answeredContribution($footprint, CarbonFootprintReportSubjectType::Company, $train, workplace: $hotel);
        $this->answeredContribution($footprint, CarbonFootprintReportSubjectType::Company, $train);
        $this->contributionRepository->add(OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Company, (string) $footprint->id(), self::ACCOUNT_ID, (string) $employee->id()));
        $ignored = OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Company, (string) $footprint->id(), self::ACCOUNT_ID);
        $ignored->ignore();
        $this->contributionRepository->add($ignored);
        ($this->syncService())(new SyncOrganizationalEmployeeActivitiesRequest(CarbonFootprintReportSubjectType::Company, (string) $footprint->id()), $this->ownerUser());

        $overview = ($this->sut)(new ListOrganizationalEmployeeContributionsRequest(CarbonFootprintReportSubjectType::Company, (string) $footprint->id()), $this->ownerUser());

        $this->assertCount(4, $overview->contributions);
        $this->assertSame(1, $overview->pendingCount);
        $this->assertSame(2, $overview->answeredCount);
        $this->assertSame(1, $overview->ignoredCount);
        $this->assertSame(50.0, $overview->answeredPercentage);
        $this->assertArrayHasKey((string) $employee->id(), $overview->employeesById);
        $this->assertArrayHasKey((string) $hotel, $overview->propertiesById);

        $rows = $overview->blockRows(Source::EmployeeCommuting->value);
        $this->assertCount(2, $rows);
        $this->assertSame([16560.0, 16560.0], array_map(static fn(array $row): float => round((float) $row['emissions']->co2eq(), 6), $rows));
        $this->assertSame(33120.0, round((float) $overview->emissionsFor(Source::EmployeeCommuting->value)->co2eq(), 6));
        $this->assertSame('0', $overview->emissionsFor(Source::BusinessTravel->value)->co2eq());
    }

    public function testUserIsDenied(): void
    {
        $footprint = $this->propertyFootprint();

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new ListOrganizationalEmployeeContributionsRequest(CarbonFootprintReportSubjectType::Property, (string) $footprint->id()), $this->user());
    }
}
