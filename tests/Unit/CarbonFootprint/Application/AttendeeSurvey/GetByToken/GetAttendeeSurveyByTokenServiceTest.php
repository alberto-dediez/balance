<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\AttendeeSurvey\GetByToken;

use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\GetByToken\GetAttendeeSurveyByTokenService;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyTokenInvalidException;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryAttendeeSurveyRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMobilityRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryEventRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use PHPUnit\Framework\TestCase;

class GetAttendeeSurveyByTokenServiceTest extends TestCase
{
    private InMemoryAttendeeSurveyRepository $attendeeSurveyRepository;
    private InMemoryEventRepository $eventRepository;
    private InMemoryMobilityRepository $mobilityRepository;
    private GetAttendeeSurveyByTokenService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->attendeeSurveyRepository = new InMemoryAttendeeSurveyRepository();
        $this->eventRepository = new InMemoryEventRepository();
        $this->mobilityRepository = new InMemoryMobilityRepository();

        $this->sut = new GetAttendeeSurveyByTokenService(
            $this->attendeeSurveyRepository,
            $this->eventRepository,
            $this->mobilityRepository,
        );
    }

    public function testReturnsTheSurveyEventAndMobilityCatalog(): void
    {
        $event = EventMother::fromNameAndAccountAndId('Event', 'account-id', (string) EventId::generate());
        $this->eventRepository->add($event);

        $attendeeSurvey = AttendeeSurveyMother::fromEventId((string) $event->id());
        $this->attendeeSurveyRepository->add($attendeeSurvey);

        $mobility = MobilityMother::registeredMobility();
        $this->mobilityRepository->add($mobility);

        $response = ($this->sut)(new GetAttendeeSurveyByTokenRequest($attendeeSurvey->token()));

        $this->assertSame($attendeeSurvey, $response->attendeeSurvey);
        $this->assertTrue($event->id()->equals($response->event->id()));
        $this->assertSame([$mobility], $response->mobilities);
    }

    public function testThrowsOnInvalidToken(): void
    {
        $this->expectException(AttendeeSurveyTokenInvalidException::class);

        ($this->sut)(new GetAttendeeSurveyByTokenRequest('invalid-token'));
    }
}
