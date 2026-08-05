<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Mobility\Register;

use App\CarbonFootprint\Application\Mobility\MobilityAuthorizer;
use App\CarbonFootprint\Application\Mobility\Register\RegisterMobilityRequest;
use App\CarbonFootprint\Application\Mobility\Register\RegisterMobilityService;
use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRegistered;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityTranslated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterMobilityServiceTest extends TestCase
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
        $repository = MobilityRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterMobilityRequest(
            name: 'Movilidad 1',
            unit: Unit::Kilometer,
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->mobilityId);
        $this->assertEquals(36, strlen((string) $result->mobilityId));
        $this->assertEquals('Movilidad 1', $result->name);

        $mobilityPersisted = $repository->find($result->mobilityId);

        $this->assertNotNull($mobilityPersisted);
        $this->assertSame(Unit::Kilometer, $mobilityPersisted->unit());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MobilityRegistered);
        $this->assertEquals('Movilidad 1', $lastEvent->name());
        $this->assertTrue($mobilityPersisted->id()->equals($lastEvent->mobilityId()));
    }

    public function testRegisterWithLocaleTranslate(): void
    {
        $repository = MobilityRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new RegisterMobilityRequest(
            name: 'changedName',
            unit: Unit::Piece,
        );

        $result = $sut($request, UserMother::adminUser());

        $mobilityPersisted = $repository->find($result->mobilityId);

        $this->assertNotNull($mobilityPersisted);
        $this->assertEquals('changedName', $mobilityPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MobilityTranslated);
        $this->assertEquals('changedName', $lastEvent->name());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($mobilityPersisted->id()->equals($lastEvent->mobilityId()));
    }

    public function testIsHandle(): void
    {
        $repository = MobilityRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut->isHandled(new RegisterMobilityRequest(
            name: 'test',
            unit: Unit::Kilometer,
        ));

        $this->assertTrue($result);
    }

    public function testRegistersTheTravelModeOfTheMobility(): void
    {
        $repository = MobilityRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut(
            new RegisterMobilityRequest(
                name: 'Avión',
                unit: Unit::Kilometer,
                travelMode: TravelMode::Flight,
            ),
            UserMother::adminUser(),
        );

        $mobilityPersisted = $repository->find($result->mobilityId);

        $this->assertNotNull($mobilityPersisted);
        $this->assertSame(TravelMode::Flight, $mobilityPersisted->travelMode());
    }

    public function testRegistersAMobilityWithoutTravelMode(): void
    {
        $repository = MobilityRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut(
            new RegisterMobilityRequest(name: 'Otro medio', unit: Unit::Kilometer),
            UserMother::adminUser(),
        );

        $mobilityPersisted = $repository->find($result->mobilityId);

        $this->assertNotNull($mobilityPersisted);
        $this->assertNull($mobilityPersisted->travelMode());
    }

    private function createSUT(MobilityRepository $repository, Locale $locale): RegisterMobilityService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterMobilityService(
            mobilityRepository: $repository,
            authorizer: new MobilityAuthorizer(),
        );
    }
}
