<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\AttendeeSurvey\GetStats;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionsAggregator;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\CarbonFootprint\Application\AttendeeSurvey\GetStats\GetAttendeeSurveyStatsRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\GetStats\GetAttendeeSurveyStatsResponse;
use App\CarbonFootprint\Application\AttendeeSurvey\GetStats\GetAttendeeSurveyStatsService;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurvey;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryAttendeeSurveyRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryAttendeeSurveyResponseRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\EventOperations\Application\Event\EventVenueLocator;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryEventRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GetAttendeeSurveyStatsServiceTest extends TestCase
{
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private InMemoryAttendeeSurveyRepository $attendeeSurveyRepository;
    private InMemoryAttendeeSurveyResponseRepository $attendeeSurveyResponseRepository;
    private InMemoryEventRepository $eventRepository;
    private InMemoryProjectContributionRepository $projectContributionRepository;
    private GetAttendeeSurveyStatsService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->eventCarbonFootprintRepository = new InMemoryEventCarbonFootprintRepository();
        $this->attendeeSurveyRepository = new InMemoryAttendeeSurveyRepository();
        $this->attendeeSurveyResponseRepository = new InMemoryAttendeeSurveyResponseRepository();
        $this->eventRepository = new InMemoryEventRepository();
        $this->projectContributionRepository = new InMemoryProjectContributionRepository();

        $this->sut = new GetAttendeeSurveyStatsService(
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
            $this->attendeeSurveyRepository,
            $this->attendeeSurveyResponseRepository,
            $this->eventRepository,
            new EventVenueLocator(new InMemoryPropertyRepository()),
            $this->projectContributionRepository,
            new ProjectContributionsAggregator(),
        );
    }

    public function testReturnsResponsesCountAndSurveyedPercentage(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $event = EventMother::fromNameAndAccountAndId('Event', 'account-id', 'event-id');
        $this->eventRepository->add($event);

        $attendeeSurvey = AttendeeSurveyMother::fromEventId('event-id');
        $this->attendeeSurveyRepository->add($attendeeSurvey);

        $this->attendeeSurveyResponseRepository->add(AttendeeSurveyResponseMother::fromAttendeeSurveyId((string) $attendeeSurvey->id()));
        $this->attendeeSurveyResponseRepository->add(AttendeeSurveyResponseMother::fromAttendeeSurveyId((string) $attendeeSurvey->id()));

        $response = ($this->sut)(
            new GetAttendeeSurveyStatsRequest($eventCarbonFootprint->id()),
            $this->ownerUser(),
        );

        $this->assertSame($attendeeSurvey, $response->attendeeSurvey);
        $this->assertSame(2, $response->responsesCount);
    }

    public function testSummarisesTheContributionsStartedFromTheAnswersOfTheSurvey(): void
    {
        $attendeeSurvey = $this->surveyWithFootprint();
        $accountId = new AccountId('account-id');
        $projectId = ProjectId::generate();

        $paidResponse = AttendeeSurveyResponseMother::withEmissions((string) $attendeeSurvey->id(), '1000');
        $pendingResponse = AttendeeSurveyResponseMother::withEmissions((string) $attendeeSurvey->id(), '1000');

        $this->attendeeSurveyResponseRepository->add($paidResponse);
        $this->attendeeSurveyResponseRepository->add($pendingResponse);

        $this->projectContributionRepository->add(ProjectContributionMother::paidFromAttendeeSurveyResponse(
            $accountId,
            $projectId,
            (string) $paidResponse->id(),
            tonnes: 1.0,
        ));
        $this->projectContributionRepository->add(ProjectContributionMother::pendingFromAttendeeSurveyResponse(
            $accountId,
            $projectId,
            (string) $pendingResponse->id(),
            tonnes: 1.0,
        ));

        $contributions = $this->stats()->contributions;

        $this->assertSame(2, $contributions->aggregation->contributions);
        $this->assertSame(1, $contributions->aggregation->paid);
        $this->assertSame(1, $contributions->aggregation->pending);
        $this->assertEqualsWithDelta(100.0, $contributions->startedRate, 0.001);
        $this->assertEqualsWithDelta(50.0, $contributions->paidRate, 0.001);
        $this->assertEqualsWithDelta(50.0, $contributions->completionRate, 0.001);
        $this->assertEqualsWithDelta(50.0, $contributions->compensatedEmissionsRate, 0.001);
    }

    public function testIgnoresTheContributionsOfAnotherOrigin(): void
    {
        $attendeeSurvey = $this->surveyWithFootprint();
        $accountId = new AccountId('account-id');
        $projectId = ProjectId::generate();

        $response = AttendeeSurveyResponseMother::withEmissions((string) $attendeeSurvey->id(), '1000');
        $this->attendeeSurveyResponseRepository->add($response);

        $this->projectContributionRepository->add(ProjectContributionMother::paid(
            $accountId,
            $projectId,
            originId: (string) $response->id(),
        ));

        $contributions = $this->stats()->contributions;

        $this->assertSame(0, $contributions->aggregation->contributions);
        $this->assertSame(0.0, $contributions->compensatedEmissionsRate);
    }

    public function testReportsNoContributionsWhenTheSurveyHasNoAnswers(): void
    {
        $this->surveyWithFootprint();

        $contributions = $this->stats()->contributions;

        $this->assertSame(0, $contributions->aggregation->contributions);
        $this->assertSame(0.0, $contributions->startedRate);
        $this->assertSame(0.0, $contributions->compensatedEmissionsRate);
    }

    public function testUnauthorizedAccess(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new GetAttendeeSurveyStatsRequest($eventCarbonFootprint->id()),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    private function surveyWithFootprint(): AttendeeSurvey
    {
        $this->eventCarbonFootprintRepository->add(EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id'));
        $this->eventRepository->add(EventMother::fromNameAndAccountAndId('Event', 'account-id', 'event-id'));

        $attendeeSurvey = AttendeeSurveyMother::fromEventId('event-id');
        $this->attendeeSurveyRepository->add($attendeeSurvey);

        return $attendeeSurvey;
    }

    private function stats(): GetAttendeeSurveyStatsResponse
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->findByEventId(new EventId('event-id'));

        $this->assertNotNull($eventCarbonFootprint);

        return ($this->sut)(new GetAttendeeSurveyStatsRequest($eventCarbonFootprint->id()), $this->ownerUser());
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
