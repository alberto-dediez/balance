<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\AttendeeSurvey;

use App\CarbonFootprint\Application\AttendeeSurvey\Reopen\ReopenAttendeeSurveyService;
use App\CarbonFootprint\Domain\Event\AttendeeSurvey\ReopenAttendeeSurveyOnEventUnlockedSubscriber;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyStatus;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryAttendeeSurveyRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventLocked;
use App\EventOperations\Domain\Model\Event\EventUnlocked;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\TestCase;

class ReopenAttendeeSurveyOnEventUnlockedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToEventUnlocked(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo(new EventUnlocked(EventId::generate())));
    }

    public function testIsNotSubscribedToUnrelatedEvents(): void
    {
        $sut = $this->createSUT();

        $this->assertFalse($sut->isSubscribedTo(new EventLocked(EventId::generate())));
    }

    public function testHandleReopensTheAttendeeSurvey(): void
    {
        $eventId = (string) EventId::generate();

        $attendeeSurvey = AttendeeSurveyMother::fromEventId($eventId);
        $attendeeSurvey->close(realAttendees: 120, confidenceLevel: ConfidenceLevel::Ninety, marginOfError: 0.05);

        $attendeeSurveyRepository = new InMemoryAttendeeSurveyRepository();
        $attendeeSurveyRepository->add($attendeeSurvey);

        $sut = $this->createSUT($attendeeSurveyRepository);
        $sut->handle(new EventUnlocked(new EventId($eventId)));

        $this->assertSame(AttendeeSurveyStatus::Open, $attendeeSurvey->status());
    }

    public function testHandleDoesNothingWhenThereIsNoAttendeeSurvey(): void
    {
        $sut = $this->createSUT();

        $sut->handle(new EventUnlocked(EventId::generate()));

        $this->addToAssertionCount(1);
    }

    private function createSUT(
        ?InMemoryAttendeeSurveyRepository $attendeeSurveyRepository = null,
    ): ReopenAttendeeSurveyOnEventUnlockedSubscriber {
        return new ReopenAttendeeSurveyOnEventUnlockedSubscriber(
            new ReopenAttendeeSurveyService($attendeeSurveyRepository ?? new InMemoryAttendeeSurveyRepository()),
            new FakeLoggedUser(UserMother::adminUser()),
        );
    }
}
