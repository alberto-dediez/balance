<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Dashboard;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionsAggregator;
use App\CarbonFootprint\Application\Dashboard\DashboardCompensationsCalculator;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseId;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepositoryMother;
use PHPUnit\Framework\TestCase;

class DashboardCompensationsCalculatorTest extends TestCase
{
    private const string ACCOUNT_ID = 'account-id-test';

    public function testItAggregatesCompensationsFundsAndVoluntaryParticipation(): void
    {
        $accountId = new AccountId(self::ACCOUNT_ID);
        $projectContributionRepository = ProjectContributionRepositoryMother::inMemory();
        $projectRepository = ProjectRepositoryMother::inMemory();
        $attendeeSurveyRepository = AttendeeSurveyRepositoryMother::inMemory();
        $attendeeSurveyResponseRepository = AttendeeSurveyResponseRepositoryMother::inMemory();
        $tripCalculationLinkRepository = TripCalculationLinkRepositoryMother::inMemory();
        $tripCalculationRepository = TripCalculationRepositoryMother::inMemory();

        $socialProject = ProjectMother::from(
            name: 'Escuela rural',
            id: (string) ProjectId::generate(),
            projectType: ProjectType::SocialProject,
        );
        $compensationProject = ProjectMother::from(
            name: 'Reforestación',
            id: (string) ProjectId::generate(),
            projectType: ProjectType::EmissionCompensation,
        );
        $projectRepository->add($socialProject);
        $projectRepository->add($compensationProject);

        $attendeeSurvey = AttendeeSurveyMother::fromEventIdAndAccount((string) EventId::generate(), self::ACCOUNT_ID);
        $attendeeSurveyRepository->add($attendeeSurvey);

        $respondedId = (string) AttendeeSurveyResponseId::generate();
        $attendeeSurveyResponseRepository->add(AttendeeSurveyResponseMother::from(
            attendeeSurveyId: (string) $attendeeSurvey->id(),
            id: $respondedId,
        ));
        $attendeeSurveyResponseRepository->add(AttendeeSurveyResponseMother::fromAttendeeSurveyId((string) $attendeeSurvey->id()));

        $tripCalculationLink = TripCalculationLinkMother::fromPropertyIdAndAccount((string) PropertyId::generate(), self::ACCOUNT_ID);
        $tripCalculationLinkRepository->add($tripCalculationLink);
        $tripCalculationRepository->add(TripCalculationMother::fromLinkId((string) $tripCalculationLink->id()));
        $tripCalculationRepository->add(TripCalculationMother::fromLinkId((string) $tripCalculationLink->id()));

        $projectContributionRepository->add(ProjectContributionMother::paidFromAttendeeSurveyResponse(
            $accountId,
            $socialProject->id(),
            $respondedId,
            tonnes: 0.5,
        ));
        $projectContributionRepository->add(ProjectContributionMother::paid(
            accountId: $accountId,
            projectId: $compensationProject->id(),
            tonnes: 2.0,
            origin: ProjectContributionOrigin::EventCarbonFootprint,
        ));
        $projectContributionRepository->add(ProjectContributionMother::pending(
            accountId: $accountId,
            projectId: $compensationProject->id(),
            tonnes: 3.0,
        ));

        $sut = new DashboardCompensationsCalculator(
            projectContributionRepository: $projectContributionRepository,
            contributionsAggregator: new ProjectContributionsAggregator(),
            projectRepository: $projectRepository,
            attendeeSurveyRepository: $attendeeSurveyRepository,
            attendeeSurveyResponseRepository: $attendeeSurveyResponseRepository,
            tripCalculationLinkRepository: $tripCalculationLinkRepository,
            tripCalculationRepository: $tripCalculationRepository,
        );

        $compensations = $sut($accountId, (int) (new \DateTimeImmutable('NOW'))->format('Y'));

        $this->assertSame(3, $compensations->aggregation->contributions);
        $this->assertSame(2, $compensations->aggregation->paid);
        $this->assertEqualsWithDelta(2500.0, $compensations->compensatedCo2eqKg, 0.01);
        $this->assertEqualsWithDelta(2500.0, $compensations->periodCompensatedCo2eqKg, 0.01);
        $this->assertEqualsWithDelta(500.0, $compensations->voluntaryCompensatedCo2eqKg, 0.01);
        $this->assertEqualsWithDelta(2000.0, $compensations->ownCompensatedCo2eqKg, 0.01);
        $this->assertEqualsWithDelta(60.5, $compensations->fundsAmount, 0.01);
        $this->assertEqualsWithDelta(60.5, $compensations->periodFundsAmount, 0.01);
        $this->assertEqualsWithDelta(12.1, $compensations->socialFundsAmount, 0.01);
        $this->assertSame(1, $compensations->attendeeSurveys);
        $this->assertSame(2, $compensations->surveyResponses);
        $this->assertSame(1, $compensations->tripCalculationLinks);
        $this->assertSame(2, $compensations->tripCalculations);
        $this->assertSame(1, $compensations->voluntaryContributors);
        $this->assertEqualsWithDelta(25.0, $compensations->voluntaryParticipationRate, 0.01);
        $this->assertSame(['Reforestación' => 48.4, 'Escuela rural' => 12.1], $compensations->fundsByProject);
    }

    public function testItReturnsEmptyCompensationsWhenThereIsNoActivity(): void
    {
        $sut = new DashboardCompensationsCalculator(
            projectContributionRepository: ProjectContributionRepositoryMother::inMemory(),
            contributionsAggregator: new ProjectContributionsAggregator(),
            projectRepository: ProjectRepositoryMother::inMemory(),
            attendeeSurveyRepository: AttendeeSurveyRepositoryMother::inMemory(),
            attendeeSurveyResponseRepository: AttendeeSurveyResponseRepositoryMother::inMemory(),
            tripCalculationLinkRepository: TripCalculationLinkRepositoryMother::inMemory(),
            tripCalculationRepository: TripCalculationRepositoryMother::inMemory(),
        );

        $compensations = $sut(null, 2026);

        $this->assertSame(0, $compensations->aggregation->contributions);
        $this->assertSame(0.0, $compensations->compensatedCo2eqKg);
        $this->assertSame(0.0, $compensations->fundsAmount);
        $this->assertSame(0.0, $compensations->voluntaryParticipationRate);
        $this->assertSame([], $compensations->compensatedByYear);
        $this->assertSame([], $compensations->fundsByProject);
    }
}
