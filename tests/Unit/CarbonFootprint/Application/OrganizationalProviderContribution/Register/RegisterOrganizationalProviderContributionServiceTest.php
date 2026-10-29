<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\Register;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\Register\RegisterOrganizationalProviderContributionRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\Register\RegisterOrganizationalProviderContributionService;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionClosedException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\ProviderContributionMode;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionServiceTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;

class RegisterOrganizationalProviderContributionServiceTest extends OrganizationalProviderContributionServiceTestCase
{
    private RegisterOrganizationalProviderContributionService $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new RegisterOrganizationalProviderContributionService(
            $this->locator,
            $this->providerRepository,
            $this->contributionRepository,
            new CompanyProperties($this->propertyRepository),
        );
    }

    public function testRegistersManualContribution(): void
    {
        $footprint = $this->propertyFootprint();
        $provider = $this->provider(linked: false);

        $response = ($this->sut)($this->request(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), $provider->id(), ProviderContributionMode::Manual), $this->owner());

        $contribution = $this->contributionRepository->find($response->contributionId);
        $this->assertNotNull($contribution);
        $this->assertSame(ProviderContributionMode::Manual, $response->mode);
        $this->assertSame(ProviderContributionStatus::Answered, $contribution->status());
        $this->assertSame('Logística Sur', $contribution->companyName());
        $this->assertSame('', $contribution->token());
        $this->assertSame([Source::UpstreamTransport], $contribution->sources());
    }

    public function testRegistersRequestedContributionWithToken(): void
    {
        $footprint = $this->propertyFootprint();
        $provider = $this->provider();

        $response = ($this->sut)($this->request(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), $provider->id(), ProviderContributionMode::Requested), $this->owner());

        $contribution = $this->contributionRepository->find($response->contributionId);
        $this->assertNotNull($contribution);
        $this->assertSame(ProviderContributionStatus::Pending, $contribution->status());
        $this->assertSame(64, strlen($contribution->token()));
    }

    public function testProviderOfAnotherAccountThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $provider = $this->provider(accountId: 'other-account');

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)($this->request(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), $provider->id(), ProviderContributionMode::Manual), $this->owner());
    }

    public function testRequestedWithoutLinkedUserThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $provider = $this->provider(linked: false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Provider has no linked user');

        ($this->sut)($this->request(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), $provider->id(), ProviderContributionMode::Requested), $this->owner());
    }

    public function testDuplicateThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $provider = $this->provider();
        ($this->sut)($this->request(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), $provider->id(), ProviderContributionMode::Manual), $this->owner());

        $this->expectException(OrganizationalProviderContributionAlreadyExistsException::class);

        ($this->sut)($this->request(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), $provider->id(), ProviderContributionMode::Requested), $this->owner());
    }

    public function testCompletedFootprintThrows(): void
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), self::ACCOUNT_ID);
        $footprint->complete(new Emissions('1', '1', '0', '0'));
        $this->propertyCarbonFootprintRepository->add($footprint);
        $provider = $this->provider();

        $this->expectException(OrganizationalProviderContributionClosedException::class);

        ($this->sut)($this->request(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), $provider->id(), ProviderContributionMode::Manual), $this->owner());
    }

    public function testUserIsDenied(): void
    {
        $footprint = $this->propertyFootprint();
        $provider = $this->provider();

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)($this->request(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), $provider->id(), ProviderContributionMode::Manual), $this->plainUser());
    }

    public function testPropertyIdOnPropertyFootprintThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $provider = $this->provider();

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)($this->request(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), $provider->id(), ProviderContributionMode::Manual, PropertyId::generate()), $this->owner());
    }

    public function testCompanyContributionOnConsolidatedProperty(): void
    {
        $companyId = CompanyId::generate();
        $footprint = $this->companyFootprint($companyId);
        $property = PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Hotel Norte', $companyId, PropertyId::generate(), OperatingRegime::Owned);
        $this->propertyRepository->add($property);
        $provider = $this->provider();

        $response = ($this->sut)($this->request(CarbonFootprintReportSubjectType::Company, (string) $footprint->id(), $provider->id(), ProviderContributionMode::Manual, $property->id()), $this->owner());

        $this->assertTrue($property->id()->equals($this->contributionRepository->find($response->contributionId)?->propertyId() ?? PropertyId::generate()));
    }

    public function testCompanyContributionOnNonConsolidatedPropertyThrows(): void
    {
        $companyId = CompanyId::generate();
        $footprint = $this->companyFootprint($companyId);
        $property = PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Hotel Franquicia', $companyId, PropertyId::generate(), OperatingRegime::Franchised);
        $this->propertyRepository->add($property);
        $provider = $this->provider();

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)($this->request(CarbonFootprintReportSubjectType::Company, (string) $footprint->id(), $provider->id(), ProviderContributionMode::Manual, $property->id()), $this->owner());
    }

    private function request(
        CarbonFootprintReportSubjectType $subjectType,
        string $carbonFootprintId,
        ProviderId $providerId,
        ProviderContributionMode $mode,
        ?PropertyId $propertyId = null,
    ): RegisterOrganizationalProviderContributionRequest {
        return new RegisterOrganizationalProviderContributionRequest($subjectType, $carbonFootprintId, $providerId, $propertyId, $mode, [Source::UpstreamTransport]);
    }
}
