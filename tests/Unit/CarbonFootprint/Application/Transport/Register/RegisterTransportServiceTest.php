<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Transport\Register;

use App\CarbonFootprint\Application\Transport\Register\RegisterTransportRequest;
use App\CarbonFootprint\Application\Transport\Register\RegisterTransportService;
use App\CarbonFootprint\Application\Transport\TransportAuthorizer;
use App\CarbonFootprint\Domain\Model\Transport\TransportRegistered;
use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;
use App\CarbonFootprint\Domain\Model\Transport\TransportTranslated;
use App\CarbonFootprint\Domain\Model\Transport\Vehicle;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Transport\TransportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterTransportServiceTest extends TestCase
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
        $repository = TransportRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterTransportRequest(
            name: 'Transport 1',
            unit: Unit::Kilogram,
            vehicle: Vehicle::PassengerCars,
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->transportId);
        $this->assertEquals(36, strlen((string) $result->transportId));
        $this->assertEquals('Transport 1', $result->name);

        $transportPersisted = $repository->find($result->transportId);

        $this->assertNotNull($transportPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof TransportRegistered);
        $this->assertEquals('Transport 1', $lastEvent->name());
        $this->assertTrue($transportPersisted->id()->equals($lastEvent->transportId()));
    }

    public function testRegisterWithLocaleTranslate(): void
    {
        $repository = TransportRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new RegisterTransportRequest(
            name: 'changedName',
            unit: Unit::Piece,
            vehicle: Vehicle::Trucks,
        );

        $result = $sut($request, UserMother::adminUser());

        $transportPersisted = $repository->find($result->transportId);

        $this->assertNotNull($transportPersisted);
        $this->assertEquals('changedName', $transportPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof TransportTranslated);
        $this->assertEquals('changedName', $lastEvent->name());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($transportPersisted->id()->equals($lastEvent->transportId()));
    }

    public function testIsHandle(): void
    {
        $repository = TransportRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut->isHandled(new RegisterTransportRequest(
            name: 'test',
            unit: Unit::Kilogram,
            vehicle: Vehicle::PassengerCars,
        ));

        $this->assertTrue($result);
    }

    private function createSUT(TransportRepository $repository, Locale $locale): RegisterTransportService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterTransportService(
            transportRepository: $repository,
            authorizer: new TransportAuthorizer(),
        );
    }
}
