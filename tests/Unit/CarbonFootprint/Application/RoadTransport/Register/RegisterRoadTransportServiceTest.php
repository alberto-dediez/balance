<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\RoadTransport\Register;

use App\CarbonFootprint\Application\RoadTransport\Register\RegisterRoadTransportRequest;
use App\CarbonFootprint\Application\RoadTransport\Register\RegisterRoadTransportService;
use App\CarbonFootprint\Application\RoadTransport\RoadTransportAuthorizer;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRegistered;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportTranslated;
use App\CarbonFootprint\Domain\Model\RoadTransport\Vehicle;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterRoadTransportServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterRoadTransportRequest(
            name: 'Road Transport 1',
            unit: Unit::Kilogram,
            vehicle: Vehicle::PassengerCars,
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->roadTransportId);
        $this->assertEquals(36, strlen((string) $result->roadTransportId));
        $this->assertEquals('Road Transport 1', $result->name);

        $roadTransportPersisted = $repository->find($result->roadTransportId);

        $this->assertNotNull($roadTransportPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof RoadTransportRegistered);
        $this->assertEquals('Road Transport 1', $lastEvent->name());
        $this->assertTrue($roadTransportPersisted->id()->equals($lastEvent->roadTransportId()));
    }

    public function testRegisterWithLocaleTranslate(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new RegisterRoadTransportRequest(
            name: 'changedName',
            unit: Unit::Piece,
            vehicle: Vehicle::Trucks,
        );

        $result = $sut($request, UserMother::adminUser());

        $roadTransportPersisted = $repository->find($result->roadTransportId);

        $this->assertNotNull($roadTransportPersisted);
        $this->assertEquals('changedName', $roadTransportPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof RoadTransportTranslated);
        $this->assertEquals('changedName', $lastEvent->name());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($roadTransportPersisted->id()->equals($lastEvent->roadTransportId()));
    }

    public function testIsHandle(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut->isHandled(new RegisterRoadTransportRequest(
            name: 'test',
            unit: Unit::Kilogram,
            vehicle: Vehicle::PassengerCars,
        ));

        $this->assertTrue($result);
    }

    private function createSUT(RoadTransportRepository $repository, Locale $locale): RegisterRoadTransportService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterRoadTransportService(
            roadTransportRepository: $repository,
            authorizer: new RoadTransportAuthorizer(),
        );
    }
}
