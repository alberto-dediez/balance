<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution\AnswerCommuting;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\AnswerCommuting\AnswerOrganizationalEmployeeContributionCommutingRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\AnswerCommuting\AnswerOrganizationalEmployeeContributionCommutingService;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\CommuteAnswer;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContribution;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionClosedException;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionStepStatus;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionServiceTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionMother;

class AnswerOrganizationalEmployeeContributionCommutingServiceTest extends OrganizationalEmployeeContributionServiceTestCase
{
    private AnswerOrganizationalEmployeeContributionCommutingService $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new AnswerOrganizationalEmployeeContributionCommutingService(
            new OrganizationalEmployeeContributionAuthenticator($this->contributionRepository),
            $this->contributionRepository,
            $this->locator,
            new CompanyProperties($this->propertyRepository),
            'v1',
        );
    }

    public function testStoresTheCommutesAndTheConsent(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = $this->pendingContribution(CarbonFootprintReportSubjectType::Property, (string) $footprint->id());

        ($this->sut)(new AnswerOrganizationalEmployeeContributionCommutingRequest(
            token: 'tok-1',
            workplacePropertyId: null,
            weeksPerYear: 46,
            commutes: [new CommuteAnswer(MobilityId::generate(), 12.0, 5, 1)],
            consentGranted: true,
            consentIp: '127.0.0.1',
        ));

        $this->assertTrue($contribution->hasConsent());
        $this->assertSame('v1', $contribution->consentProof()?->policyVersion());
        $this->assertCount(1, $contribution->commutes());
        $this->assertSame(OrganizationalEmployeeContributionStepStatus::CommutingAnswered, $contribution->stepStatus());
    }

    public function testWithoutConsentNoProofIsStored(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = $this->pendingContribution(CarbonFootprintReportSubjectType::Property, (string) $footprint->id());

        ($this->sut)(new AnswerOrganizationalEmployeeContributionCommutingRequest(
            token: 'tok-1',
            workplacePropertyId: null,
            weeksPerYear: 46,
            commutes: [new CommuteAnswer(MobilityId::generate(), 12.0, 5, 1)],
            consentGranted: false,
            consentIp: '127.0.0.1',
        ));

        $this->assertFalse($contribution->hasConsent());
        $this->assertNull($contribution->consentProof());
    }

    public function testAnIgnoredContributionThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = $this->pendingContribution(CarbonFootprintReportSubjectType::Property, (string) $footprint->id());
        $contribution->ignore();

        $this->expectException(OrganizationalEmployeeContributionIgnoredException::class);

        ($this->sut)(new AnswerOrganizationalEmployeeContributionCommutingRequest('tok-1', null, null, []));
    }

    public function testInACompanyTheWorkplaceIsStored(): void
    {
        $companyId = CompanyId::generate();
        $hotel = $this->consolidatedProperty($companyId);
        $footprint = $this->companyFootprint($companyId);
        $contribution = $this->pendingContribution(CarbonFootprintReportSubjectType::Company, (string) $footprint->id());

        ($this->sut)(new AnswerOrganizationalEmployeeContributionCommutingRequest('tok-1', $hotel, null, []));

        $this->assertNotNull($contribution->workplacePropertyId());
        $this->assertTrue($hotel->equals($contribution->workplacePropertyId()));
    }

    public function testANonConsolidatedWorkplaceThrows(): void
    {
        $companyId = CompanyId::generate();
        $franchise = $this->nonConsolidatedProperty($companyId);
        $footprint = $this->companyFootprint($companyId);
        $this->pendingContribution(CarbonFootprintReportSubjectType::Company, (string) $footprint->id());

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new AnswerOrganizationalEmployeeContributionCommutingRequest('tok-1', $franchise, null, []));
    }

    public function testAWorkplaceInAPropertyFootprintThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $this->pendingContribution(CarbonFootprintReportSubjectType::Property, (string) $footprint->id());

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new AnswerOrganizationalEmployeeContributionCommutingRequest('tok-1', PropertyId::generate(), null, []));
    }

    public function testACompletedFootprintIsClosed(): void
    {
        $footprint = $this->completedPropertyFootprint();
        $this->pendingContribution(CarbonFootprintReportSubjectType::Property, (string) $footprint->id());

        $this->expectException(OrganizationalEmployeeContributionClosedException::class);

        ($this->sut)(new AnswerOrganizationalEmployeeContributionCommutingRequest('tok-1', null, null, []));
    }

    private function pendingContribution(CarbonFootprintReportSubjectType $subjectType, string $footprintId): OrganizationalEmployeeContribution
    {
        $contribution = OrganizationalEmployeeContributionMother::forFootprint($subjectType, $footprintId, self::ACCOUNT_ID, token: 'tok-1');
        $this->contributionRepository->add($contribution);

        return $contribution;
    }
}
