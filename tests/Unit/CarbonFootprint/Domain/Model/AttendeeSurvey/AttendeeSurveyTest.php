<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurvey;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyClosed;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyId;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRegistered;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyReopened;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyStatus;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use PHPUnit\Framework\TestCase;

class AttendeeSurveyTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    private function registeredAttendeeSurvey(): AttendeeSurvey
    {
        $attendeeSurvey = new AttendeeSurvey(AttendeeSurveyId::generate());
        $attendeeSurvey->register(
            eventId: EventId::generate(),
            accountId: AccountId::generate(),
            token: AttendeeSurvey::generateToken(),
        );

        return $attendeeSurvey;
    }

    public function testRegisterStartsOpenWithoutClosureData(): void
    {
        $attendeeSurvey = $this->registeredAttendeeSurvey();

        $this->assertSame(AttendeeSurveyStatus::Open, $attendeeSurvey->status());
        $this->assertFalse($attendeeSurvey->status()->isClosed());
        $this->assertNull($attendeeSurvey->realAttendees());
        $this->assertNull($attendeeSurvey->confidenceLevel());
        $this->assertNull($attendeeSurvey->marginOfError());
        $this->assertNull($attendeeSurvey->closedAt());
        $this->assertNotEmpty($attendeeSurvey->token());
        $this->assertEventPublished($attendeeSurvey, AttendeeSurveyRegistered::class);
    }

    public function testCloseTransitionsToClosedAndStoresData(): void
    {
        $attendeeSurvey = $this->registeredAttendeeSurvey();

        $attendeeSurvey->close(realAttendees: 120, confidenceLevel: ConfidenceLevel::Ninety, marginOfError: 0.05);

        $this->assertSame(AttendeeSurveyStatus::Closed, $attendeeSurvey->status());
        $this->assertTrue($attendeeSurvey->status()->isClosed());
        $this->assertSame(120, $attendeeSurvey->realAttendees());
        $this->assertSame(ConfidenceLevel::Ninety, $attendeeSurvey->confidenceLevel());
        $this->assertSame(0.05, $attendeeSurvey->marginOfError());
        $this->assertNotNull($attendeeSurvey->closedAt());
        $this->assertEventPublished($attendeeSurvey, AttendeeSurveyClosed::class);
    }

    public function testReCloseUpdatesClosureData(): void
    {
        $attendeeSurvey = $this->registeredAttendeeSurvey();

        $attendeeSurvey->close(realAttendees: 120, confidenceLevel: ConfidenceLevel::Ninety, marginOfError: 0.05);
        $attendeeSurvey->close(realAttendees: 150, confidenceLevel: ConfidenceLevel::NinetyFive, marginOfError: 0.03);

        $this->assertSame(AttendeeSurveyStatus::Closed, $attendeeSurvey->status());
        $this->assertSame(150, $attendeeSurvey->realAttendees());
        $this->assertSame(ConfidenceLevel::NinetyFive, $attendeeSurvey->confidenceLevel());
        $this->assertSame(0.03, $attendeeSurvey->marginOfError());
    }

    public function testReopenTransitionsToOpenAndClearsClosureData(): void
    {
        $attendeeSurvey = $this->registeredAttendeeSurvey();
        $attendeeSurvey->close(realAttendees: 120, confidenceLevel: ConfidenceLevel::Ninety, marginOfError: 0.05);

        $attendeeSurvey->reopen();

        $this->assertSame(AttendeeSurveyStatus::Open, $attendeeSurvey->status());
        $this->assertFalse($attendeeSurvey->status()->isClosed());
        $this->assertNull($attendeeSurvey->realAttendees());
        $this->assertNull($attendeeSurvey->confidenceLevel());
        $this->assertNull($attendeeSurvey->marginOfError());
        $this->assertNull($attendeeSurvey->closedAt());
        $this->assertEventPublished($attendeeSurvey, AttendeeSurveyReopened::class);
    }

    public function testReopeningANotClosedSurveyDoesNothing(): void
    {
        $attendeeSurvey = $this->registeredAttendeeSurvey();

        $attendeeSurvey->reopen();

        $this->assertSame(AttendeeSurveyStatus::Open, $attendeeSurvey->status());
        $events = array_filter($attendeeSurvey->recordedEvents(), static fn($e) => $e instanceof AttendeeSurveyReopened);
        $this->assertEmpty($events);
    }

    private function assertEventPublished(mixed $aggregate, string $eventClass): void
    {
        $events = array_filter($aggregate->recordedEvents(), static fn($e) => $e instanceof $eventClass);
        $this->assertNotEmpty($events);
    }
}
