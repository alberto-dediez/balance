<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\AttendeeSurvey;

use App\CarbonFootprint\Application\AttendeeSurvey\Register\RegisterAttendeeSurveyService;
use App\CarbonFootprint\Domain\Event\AttendeeSurvey\RegisterAttendeeSurveyOnEventRegisteredSubscriber;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryAttendeeSurveyRepository;
use App\EventOperations\Domain\Model\Event\DateRange;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRegistered;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\EventOperations\Domain\Model\Event\EventUpdated;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\TestCase;

class RegisterAttendeeSurveyOnEventRegisteredSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToEventRegistered(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo($this->eventRegistered((string) EventId::generate())));
    }

    public function testIsNotSubscribedToUnrelatedEvents(): void
    {
        $sut = $this->createSUT();

        $this->assertFalse($sut->isSubscribedTo($this->eventUpdated((string) EventId::generate())));
    }

    public function testHandleRegistersTheAttendeeSurvey(): void
    {
        $eventId = (string) EventId::generate();
        $eventRepository = EventRepositoryMother::inMemory();
        $eventRepository->add(EventMother::fromNameAndAccountAndId('Event', 'account-id', $eventId));

        $attendeeSurveyRepository = new InMemoryAttendeeSurveyRepository();

        $sut = $this->createSUT($eventRepository, $attendeeSurveyRepository);
        $sut->handle($this->eventRegistered($eventId));

        $attendeeSurvey = $attendeeSurveyRepository->findByEventId(new EventId($eventId));
        $this->assertNotNull($attendeeSurvey);
    }

    public function testHandleDoesNothingWithoutALoggedUser(): void
    {
        $eventId = (string) EventId::generate();
        $eventRepository = EventRepositoryMother::inMemory();
        $eventRepository->add(EventMother::fromNameAndAccountAndId('Event', 'account-id', $eventId));

        $attendeeSurveyRepository = new InMemoryAttendeeSurveyRepository();

        $sut = new RegisterAttendeeSurveyOnEventRegisteredSubscriber(
            new RegisterAttendeeSurveyService($attendeeSurveyRepository, $eventRepository),
            new FakeLoggedUser(null),
        );
        $sut->handle($this->eventRegistered($eventId));

        $attendeeSurvey = $attendeeSurveyRepository->findByEventId(new EventId($eventId));
        $this->assertNull($attendeeSurvey);
    }

    private function eventRegistered(string $eventId): EventRegistered
    {
        return new EventRegistered(
            eventId: new EventId($eventId),
            name: 'Event',
            location: null,
            propertyId: null,
            companyId: null,
            size: null,
            estimatedAttendees: null,
            sector: null,
            typology: null,
            assembly: new DateRange(),
            celebration: new DateRange(),
            breakdown: new DateRange(),
            accountId: new AccountId('account-id'),
        );
    }

    private function eventUpdated(string $eventId): EventUpdated
    {
        return new EventUpdated(
            eventId: new EventId($eventId),
            name: 'Event',
            location: null,
            propertyId: null,
            companyId: null,
            size: null,
            estimatedAttendees: null,
            sector: null,
            typology: null,
            assembly: new DateRange(),
            celebration: new DateRange(),
            breakdown: new DateRange(),
        );
    }

    private function createSUT(
        ?EventRepository $eventRepository = null,
        ?InMemoryAttendeeSurveyRepository $attendeeSurveyRepository = null,
    ): RegisterAttendeeSurveyOnEventRegisteredSubscriber {
        return new RegisterAttendeeSurveyOnEventRegisteredSubscriber(
            new RegisterAttendeeSurveyService(
                $attendeeSurveyRepository ?? new InMemoryAttendeeSurveyRepository(),
                $eventRepository ?? EventRepositoryMother::inMemory(),
            ),
            new FakeLoggedUser(UserMother::adminUser()),
        );
    }
}
