<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\AttendeeSurvey\GetStats;

use App\CarbonFootprint\Application\AttendeeSurvey\GetStats\GetAttendeeSurveyStatsRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\GetStats\GetAttendeeSurveyStatsService;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryAttendeeSurveyRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryAttendeeSurveyResponseRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryEventRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
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
    private GetAttendeeSurveyStatsService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->eventCarbonFootprintRepository = new InMemoryEventCarbonFootprintRepository();
        $this->attendeeSurveyRepository = new InMemoryAttendeeSurveyRepository();
        $this->attendeeSurveyResponseRepository = new InMemoryAttendeeSurveyResponseRepository();
        $this->eventRepository = new InMemoryEventRepository();

        $this->sut = new GetAttendeeSurveyStatsService(
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
            $this->attendeeSurveyRepository,
            $this->attendeeSurveyResponseRepository,
            $this->eventRepository,
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

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
