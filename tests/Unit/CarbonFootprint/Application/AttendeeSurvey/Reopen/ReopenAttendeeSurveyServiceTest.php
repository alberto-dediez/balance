<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\AttendeeSurvey\Reopen;

use App\CarbonFootprint\Application\AttendeeSurvey\Reopen\ReopenAttendeeSurveyRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\Reopen\ReopenAttendeeSurveyService;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyReopened;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyStatus;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ReopenAttendeeSurveyServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = AttendeeSurveyRepositoryMother::inMemory();

        $eventId = (string) EventId::generate();

        $attendeeSurvey = AttendeeSurveyMother::fromEventId($eventId);
        $attendeeSurvey->close(realAttendees: 120, confidenceLevel: ConfidenceLevel::Ninety, marginOfError: 0.05);

        $repository->add($attendeeSurvey);

        $sut = $this->createSUT($repository);

        $sut(new ReopenAttendeeSurveyRequest(new EventId($eventId)), UserMother::adminUser());

        $this->assertSame(AttendeeSurveyStatus::Open, $attendeeSurvey->status());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof AttendeeSurveyReopened);
    }

    public function testReopeningANotClosedSurveyDoesNothing(): void
    {
        $repository = AttendeeSurveyRepositoryMother::inMemory();

        $eventId = (string) EventId::generate();

        $attendeeSurvey = AttendeeSurveyMother::fromEventId($eventId);

        $repository->add($attendeeSurvey);

        $eventsBefore = $this->spySubscriber->events();

        $sut = $this->createSUT($repository);

        $sut(new ReopenAttendeeSurveyRequest(new EventId($eventId)), UserMother::adminUser());

        $this->assertSame(AttendeeSurveyStatus::Open, $attendeeSurvey->status());
        $this->assertCount(count($eventsBefore), $this->spySubscriber->events());
    }

    public function testDoesNothingWhenThereIsNoAttendeeSurvey(): void
    {
        $eventsBefore = $this->spySubscriber->events();

        $sut = $this->createSUT(AttendeeSurveyRepositoryMother::inMemory());

        $sut(new ReopenAttendeeSurveyRequest(EventId::generate()), UserMother::adminUser());

        $this->assertCount(count($eventsBefore), $this->spySubscriber->events());
    }

    private function createSUT(AttendeeSurveyRepository $repository): ReopenAttendeeSurveyService
    {
        return new ReopenAttendeeSurveyService(
            attendeeSurveyRepository: $repository,
        );
    }
}
