<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\RecordManualEmissions;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\RecordManualEmissions\RecordOrganizationalProviderContributionManualEmissionsRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\RecordManualEmissions\RecordOrganizationalProviderContributionManualEmissionsService;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ReportedEmissionsAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionClosedException;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionServiceTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionMother;

class RecordOrganizationalProviderContributionManualEmissionsServiceTest extends OrganizationalProviderContributionServiceTestCase
{
    private RecordOrganizationalProviderContributionManualEmissionsService $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new RecordOrganizationalProviderContributionManualEmissionsService(
            $this->contributionRepository,
            $this->authorizer,
            $this->locator,
            $this->synchronizer(),
        );
    }

    public function testRecordsActivitiesTaggedWithProvider(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = OrganizationalProviderContributionMother::manual(carbonFootprintId: (string) $footprint->id(), providerId: (string) ProviderId::generate());
        $this->contributionRepository->add($contribution);
        $providerId = $contribution->providerId();
        $this->assertNotNull($providerId);

        ($this->sut)(new RecordOrganizationalProviderContributionManualEmissionsRequest($contribution->id(), [
            new SectionAnswer(Scope::Scope3->reportedSectionFor(Source::UpstreamTransport), [
                new ReportedEmissionsAnswer('Reparto 2025', new Quantity(3200.0, Unit::Kilogram)),
                new ReportedEmissionsAnswer('Última milla', new Quantity(0.8, Unit::Tonne)),
            ]),
        ]), $this->owner());

        $activityIds = $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, $providerId));
        $this->assertCount(2, $activityIds);
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport)));

        $total = 0.0;
        foreach ($this->activityRepository->findByIds($activityIds) as $activity) {
            $total += (float) $activity->emissions()->co2eq();
        }
        $this->assertEqualsWithDelta(4000.0, $total, 0.001);
    }

    public function testRecordsActivitiesWithPropertyOfTheContributionInCompanyFootprint(): void
    {
        $footprint = $this->companyFootprint();
        $propertyId = PropertyId::generate();
        $contribution = OrganizationalProviderContributionMother::manual(
            carbonFootprintId: (string) $footprint->id(),
            providerId: (string) ProviderId::generate(),
            subjectType: CarbonFootprintReportSubjectType::Company,
            propertyId: (string) $propertyId,
        );
        $this->contributionRepository->add($contribution);
        $providerId = $contribution->providerId();
        $this->assertNotNull($providerId);

        ($this->sut)(new RecordOrganizationalProviderContributionManualEmissionsRequest($contribution->id(), [
            new SectionAnswer(Scope::Scope3->reportedSectionFor(Source::UpstreamTransport), [
                new ReportedEmissionsAnswer('Reparto 2025', new Quantity(100.0, Unit::Kilogram)),
            ]),
        ]), $this->owner());

        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, $providerId, false, $propertyId)));
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, $providerId)));
    }

    public function testUncoveredSectionThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = OrganizationalProviderContributionMother::manual(carbonFootprintId: (string) $footprint->id());
        $this->contributionRepository->add($contribution);

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new RecordOrganizationalProviderContributionManualEmissionsRequest($contribution->id(), [
            new SectionAnswer(Scope::Scope3->reportedSectionFor(Source::OperationsWaste), [
                new ReportedEmissionsAnswer('Residuos', new Quantity(10.0, Unit::Kilogram)),
            ]),
        ]), $this->owner());
    }

    public function testRequestedModeThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $footprint->id());
        $this->contributionRepository->add($contribution);

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new RecordOrganizationalProviderContributionManualEmissionsRequest($contribution->id(), []), $this->owner());
    }

    public function testUserIsDenied(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = OrganizationalProviderContributionMother::manual(carbonFootprintId: (string) $footprint->id());
        $this->contributionRepository->add($contribution);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new RecordOrganizationalProviderContributionManualEmissionsRequest($contribution->id(), []), $this->plainUser());
    }

    public function testCompletedFootprintThrows(): void
    {
        $footprint = $this->completedPropertyFootprint();
        $contribution = OrganizationalProviderContributionMother::manual(carbonFootprintId: (string) $footprint->id(), providerId: (string) ProviderId::generate());
        $this->contributionRepository->add($contribution);

        $this->expectException(OrganizationalProviderContributionClosedException::class);

        ($this->sut)(new RecordOrganizationalProviderContributionManualEmissionsRequest($contribution->id(), []), $this->owner());
    }
}
