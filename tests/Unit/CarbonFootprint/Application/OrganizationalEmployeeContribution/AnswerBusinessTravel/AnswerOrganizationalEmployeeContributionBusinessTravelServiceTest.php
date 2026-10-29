<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution\AnswerBusinessTravel;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\AnswerBusinessTravel\AnswerOrganizationalEmployeeContributionBusinessTravelRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\AnswerBusinessTravel\AnswerOrganizationalEmployeeContributionBusinessTravelService;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionClosedException;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\TripAnswer;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionServiceTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionMother;

class AnswerOrganizationalEmployeeContributionBusinessTravelServiceTest extends OrganizationalEmployeeContributionServiceTestCase
{
    private AnswerOrganizationalEmployeeContributionBusinessTravelService $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new AnswerOrganizationalEmployeeContributionBusinessTravelService(
            new OrganizationalEmployeeContributionAuthenticator($this->contributionRepository),
            $this->contributionRepository,
            $this->locator,
        );
    }

    public function testStoresTheTripsAndMarksTheContributionAsAnswered(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), self::ACCOUNT_ID, token: 'tok-1');
        $this->contributionRepository->add($contribution);

        ($this->sut)(new AnswerOrganizationalEmployeeContributionBusinessTravelRequest('tok-1', [new TripAnswer(MobilityId::generate(), 1200.0, 1, 1, 2)]));

        $this->assertTrue($contribution->status()->isAnswered());
        $this->assertCount(1, $contribution->trips());
    }

    public function testACompletedFootprintIsClosed(): void
    {
        $footprint = $this->completedPropertyFootprint();
        $this->contributionRepository->add(OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), self::ACCOUNT_ID, token: 'tok-1'));

        $this->expectException(OrganizationalEmployeeContributionClosedException::class);

        ($this->sut)(new AnswerOrganizationalEmployeeContributionBusinessTravelRequest('tok-1', []));
    }

    public function testAnIgnoredContributionIsRefused(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = OrganizationalEmployeeContributionMother::forFootprint(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), self::ACCOUNT_ID, token: 'tok-1');
        $contribution->ignore();
        $this->contributionRepository->add($contribution);

        $this->expectException(OrganizationalEmployeeContributionIgnoredException::class);

        ($this->sut)(new AnswerOrganizationalEmployeeContributionBusinessTravelRequest('tok-1', []));
    }
}
