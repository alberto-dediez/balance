<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution\GetByToken;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\GetByToken\GetOrganizationalEmployeeContributionByTokenRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\GetByToken\GetOrganizationalEmployeeContributionByTokenService;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionTokenInvalidException;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyRepository;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionServiceTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;

class GetOrganizationalEmployeeContributionByTokenServiceTest extends OrganizationalEmployeeContributionServiceTestCase
{
    private InMemoryCompanyRepository $companyRepository;
    private GetOrganizationalEmployeeContributionByTokenService $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyRepository = new InMemoryCompanyRepository();
        $this->sut = new GetOrganizationalEmployeeContributionByTokenService(
            new OrganizationalEmployeeContributionAuthenticator($this->contributionRepository),
            $this->locator,
            $this->propertyRepository,
            $this->companyRepository,
            new CompanyProperties($this->propertyRepository),
            $this->mobilityRepository,
        );
    }

    public function testReturnsTheMobilitiesAndNoWorkplacesInAPropertyFootprint(): void
    {
        $propertyId = PropertyId::generate();
        $this->propertyRepository->add(PropertyMother::from($propertyId, 'Hotel Sol'));
        $footprint = PropertyCarbonFootprintMother::withProvidersAnswered((string) $propertyId, 'footprint-1', self::ACCOUNT_ID, 2025);
        $this->propertyCarbonFootprintRepository->add($footprint);
        $this->contributionRepository->add(OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Property, 'footprint-1', self::ACCOUNT_ID, token: 'tok-1'));
        $this->mobility();
        $this->mobility(name: 'Coche');

        $response = ($this->sut)(new GetOrganizationalEmployeeContributionByTokenRequest('tok-1'));

        $this->assertSame('Hotel Sol', $response->subjectName);
        $this->assertSame(2025, $response->year);
        $this->assertCount(2, $response->mobilities);
        $this->assertSame([], $response->workplaces);
    }

    public function testReturnsTheConsolidatedPropertiesInACompanyFootprint(): void
    {
        $companyId = CompanyId::generate();
        $this->companyRepository->add(CompanyMother::fromNameAndId('Grupo Mar', (string) $companyId));
        $this->consolidatedProperty($companyId, 'Hotel Mar');
        $this->nonConsolidatedProperty($companyId);
        $footprint = $this->companyFootprint($companyId);
        $this->contributionRepository->add(OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Company, (string) $footprint->id(), self::ACCOUNT_ID, token: 'tok-1'));

        $response = ($this->sut)(new GetOrganizationalEmployeeContributionByTokenRequest('tok-1'));

        $this->assertSame('Grupo Mar', $response->subjectName);
        $this->assertCount(1, $response->workplaces);
        $this->assertSame('Hotel Mar', $response->workplaces[0]->name());
    }

    public function testAnUnknownTokenThrows(): void
    {
        $this->expectException(OrganizationalEmployeeContributionTokenInvalidException::class);

        ($this->sut)(new GetOrganizationalEmployeeContributionByTokenRequest('tok-unknown'));
    }
}
