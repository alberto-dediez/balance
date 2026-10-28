<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\GetGeneralInfo;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\GetGeneralInfo\GetOrganizationalProviderContributionGeneralInfoRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\GetGeneralInfo\GetOrganizationalProviderContributionGeneralInfoService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionClosedException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionTokenInvalidException;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyRepository;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionPublicServiceTestCase;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;

class GetOrganizationalProviderContributionGeneralInfoServiceTest extends OrganizationalProviderContributionPublicServiceTestCase
{
    private InMemoryCompanyRepository $companyRepository;
    private GetOrganizationalProviderContributionGeneralInfoService $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyRepository = CompanyRepositoryMother::inMemory();
        $this->sut = new GetOrganizationalProviderContributionGeneralInfoService(
            $this->authenticator,
            $this->propertyRepository,
            $this->companyRepository,
        );
    }

    public function testReturnsPropertyNameAndYear(): void
    {
        $footprint = $this->propertyFootprint();
        $propertyId = $footprint->propertyId();
        $this->assertInstanceOf(PropertyId::class, $propertyId);
        $this->propertyRepository->add(PropertyMother::fromNameAndAccountAndId('Hotel Sol', self::ACCOUNT_ID, (string) $propertyId));
        $contribution = $this->requestedContribution((string) $footprint->id(), $this->linkedProvider());

        $response = ($this->sut)(new GetOrganizationalProviderContributionGeneralInfoRequest($contribution->token()));

        $this->assertSame($contribution, $response->contribution);
        $this->assertSame('Hotel Sol', $response->subjectName);
        $this->assertSame($footprint->year(), $response->year);
    }

    public function testReturnsCompanyName(): void
    {
        $companyId = CompanyId::generate();
        $footprint = $this->companyFootprint($companyId);
        $this->companyRepository->add(CompanyMother::fromNameAndAccountAndId('Grupo Mar', self::ACCOUNT_ID, (string) $companyId));
        $contribution = $this->requestedContribution((string) $footprint->id(), $this->linkedProvider(), subjectType: CarbonFootprintReportSubjectType::Company);

        $response = ($this->sut)(new GetOrganizationalProviderContributionGeneralInfoRequest($contribution->token()));

        $this->assertSame('Grupo Mar', $response->subjectName);
    }

    public function testMissingSubjectThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = $this->requestedContribution((string) $footprint->id(), $this->linkedProvider());

        $this->expectException(OrganizationalProviderContributionTokenInvalidException::class);

        ($this->sut)(new GetOrganizationalProviderContributionGeneralInfoRequest($contribution->token()));
    }

    public function testCompletedFootprintThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $propertyId = $footprint->propertyId();
        $this->assertInstanceOf(PropertyId::class, $propertyId);
        $this->propertyRepository->add(PropertyMother::fromNameAndAccountAndId('Hotel Sol', self::ACCOUNT_ID, (string) $propertyId));
        $footprint->answerProviders();
        $footprint->complete($this->completedEmissions());
        $contribution = $this->requestedContribution((string) $footprint->id(), $this->linkedProvider());

        $this->expectException(OrganizationalProviderContributionClosedException::class);

        ($this->sut)(new GetOrganizationalProviderContributionGeneralInfoRequest($contribution->token()));
    }
}
