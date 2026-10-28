<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\AnswerEmissions;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\AnswerEmissions\AnswerOrganizationalProviderContributionEmissionsRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\AnswerEmissions\AnswerOrganizationalProviderContributionEmissionsService;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ReportedEmissionsAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionClosedException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionConsentRequiredException;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Property\Domain\Model\Property\PropertyId;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionPublicServiceTestCase;

class AnswerOrganizationalProviderContributionEmissionsServiceTest extends OrganizationalProviderContributionPublicServiceTestCase
{
    private AnswerOrganizationalProviderContributionEmissionsService $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new AnswerOrganizationalProviderContributionEmissionsService(
            $this->authenticator,
            $this->contributionRepository,
            $this->locator,
            $this->synchronizer(),
        );
    }

    public function testRecordsActivitiesTaggedWithProviderAndMarksAnswered(): void
    {
        $footprint = $this->propertyFootprint();
        $provider = $this->linkedProvider();
        $contribution = $this->requestedContribution((string) $footprint->id(), $provider, consented: true);

        ($this->sut)(new AnswerOrganizationalProviderContributionEmissionsRequest($contribution->token(), [
            new SectionAnswer(Scope::Scope3->reportedSectionFor(Source::UpstreamTransport), [
                new ReportedEmissionsAnswer('Reparto 2025', new Quantity(3200.0, Unit::Kilogram)),
                new ReportedEmissionsAnswer('Última milla', new Quantity(0.8, Unit::Tonne)),
            ]),
        ]));

        $activityIds = $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, $provider->id()));
        $this->assertCount(2, $activityIds);
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport)));
        $this->assertSame(ProviderContributionStatus::Answered, $contribution->status());

        $total = 0.0;
        foreach ($this->activityRepository->findByIds($activityIds) as $activity) {
            $total += (float) $activity->emissions()->co2eq();
        }
        $this->assertEqualsWithDelta(4000.0, $total, 0.001);
    }

    public function testRecordsActivitiesWithPropertyOfTheContributionInCompanyFootprint(): void
    {
        $footprint = $this->companyFootprint();
        $provider = $this->linkedProvider();
        $propertyId = PropertyId::generate();
        $contribution = $this->requestedContribution(
            (string) $footprint->id(),
            $provider,
            subjectType: CarbonFootprintReportSubjectType::Company,
            propertyId: (string) $propertyId,
            consented: true,
        );

        ($this->sut)(new AnswerOrganizationalProviderContributionEmissionsRequest($contribution->token(), [
            new SectionAnswer(Scope::Scope3->reportedSectionFor(Source::UpstreamTransport), [
                new ReportedEmissionsAnswer('Reparto 2025', new Quantity(100.0, Unit::Kilogram)),
            ]),
        ]));

        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, $provider->id(), false, $propertyId)));
        $this->assertCount(0, $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, $provider->id())));
    }

    public function testCompletedFootprintThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $footprint->answerProviders();
        $footprint->complete($this->completedEmissions());
        $contribution = $this->requestedContribution((string) $footprint->id(), $this->linkedProvider());

        $this->expectException(OrganizationalProviderContributionClosedException::class);

        ($this->sut)(new AnswerOrganizationalProviderContributionEmissionsRequest($contribution->token(), []));
    }

    public function testWithoutConsentThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = $this->requestedContribution((string) $footprint->id(), $this->linkedProvider());

        $this->expectException(OrganizationalProviderContributionConsentRequiredException::class);

        ($this->sut)(new AnswerOrganizationalProviderContributionEmissionsRequest($contribution->token(), [
            new SectionAnswer(Scope::Scope3->reportedSectionFor(Source::UpstreamTransport), [
                new ReportedEmissionsAnswer('Reparto 2025', new Quantity(3200.0, Unit::Kilogram)),
            ]),
        ]));
    }

    public function testUncoveredSectionThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = $this->requestedContribution((string) $footprint->id(), $this->linkedProvider(), consented: true);

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new AnswerOrganizationalProviderContributionEmissionsRequest($contribution->token(), [
            new SectionAnswer(Scope::Scope3->reportedSectionFor(Source::OperationsWaste), [
                new ReportedEmissionsAnswer('Residuos', new Quantity(10.0, Unit::Kilogram)),
            ]),
        ]));
    }
}
