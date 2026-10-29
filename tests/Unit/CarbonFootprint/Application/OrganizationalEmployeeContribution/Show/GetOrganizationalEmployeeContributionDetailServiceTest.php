<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution\Show;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Show\GetOrganizationalEmployeeContributionDetailRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Show\GetOrganizationalEmployeeContributionDetailService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\CommuteAnswer;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionServiceTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionMother;

class GetOrganizationalEmployeeContributionDetailServiceTest extends OrganizationalEmployeeContributionServiceTestCase
{
    private GetOrganizationalEmployeeContributionDetailService $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new GetOrganizationalEmployeeContributionDetailService(
            $this->contributionRepository,
            $this->authorizer,
            $this->employeeRepository,
            $this->propertyRepository,
            $this->mobilityRepository,
        );
    }

    public function testReturnsTheContributionWithItsEmployeeWorkplaceAndMobilities(): void
    {
        $companyId = CompanyId::generate();
        $hotel = $this->consolidatedProperty($companyId, 'Hotel Mar');
        $footprint = $this->companyFootprint($companyId);
        $employee = $this->employee(name: 'Ana');
        $train = $this->mobility();
        $contribution = OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Company, (string) $footprint->id(), self::ACCOUNT_ID, (string) $employee->id());
        $contribution->answerCommuting($hotel, 46, [new CommuteAnswer($train->id(), 12.0, 5, 1)]);
        $this->contributionRepository->add($contribution);

        $response = ($this->sut)(new GetOrganizationalEmployeeContributionDetailRequest($contribution->id()), $this->ownerUser());

        $this->assertSame($contribution, $response->contribution);
        $this->assertSame('Ana', $response->employee?->name());
        $this->assertSame('Hotel Mar', $response->workplace?->name());
        $this->assertArrayHasKey((string) $train->id(), $response->mobilitiesById);
    }

    public function testAnotherAccountIsDenied(): void
    {
        $contribution = OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Property, 'footprint-1', self::ACCOUNT_ID);
        $this->contributionRepository->add($contribution);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new GetOrganizationalEmployeeContributionDetailRequest($contribution->id()), $this->ownerUser('other-account'));
    }
}
