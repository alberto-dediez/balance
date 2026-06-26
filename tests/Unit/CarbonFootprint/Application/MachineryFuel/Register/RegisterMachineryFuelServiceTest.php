<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MachineryFuel\Register;

use App\CarbonFootprint\Application\MachineryFuel\MachineryFuelAuthorizer;
use App\CarbonFootprint\Application\MachineryFuel\Register\RegisterMachineryFuelRequest;
use App\CarbonFootprint\Application\MachineryFuel\Register\RegisterMachineryFuelService;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRegistered;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelTranslated;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryType;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterMachineryFuelServiceTest extends TestCase
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
        $repository = MachineryFuelRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterMachineryFuelRequest(
            name: 'Maquinaria agrícola 1',
            unit: Unit::Liter,
            machineryType: MachineryType::Agricultural,
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->machineryFuelId);
        $this->assertEquals(36, strlen((string) $result->machineryFuelId));
        $this->assertEquals('Maquinaria agrícola 1', $result->name);

        $persisted = $repository->find($result->machineryFuelId);

        $this->assertNotNull($persisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MachineryFuelRegistered);
        $this->assertEquals('Maquinaria agrícola 1', $lastEvent->name());
        $this->assertTrue($persisted->id()->equals($lastEvent->machineryFuelId()));
    }

    public function testRegisterWithLocaleTranslate(): void
    {
        $repository = MachineryFuelRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new RegisterMachineryFuelRequest(
            name: 'Agricultural machinery 1',
            unit: Unit::Liter,
            machineryType: MachineryType::Agricultural,
        );

        $result = $sut($request, UserMother::adminUser());

        $persisted = $repository->find($result->machineryFuelId);

        $this->assertNotNull($persisted);
        $this->assertEquals('Agricultural machinery 1', $persisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MachineryFuelTranslated);
        $this->assertEquals('Agricultural machinery 1', $lastEvent->name());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($persisted->id()->equals($lastEvent->machineryFuelId()));
    }

    public function testIsHandle(): void
    {
        $repository = MachineryFuelRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut->isHandled(new RegisterMachineryFuelRequest(
            name: 'test',
            unit: Unit::Liter,
        ));

        $this->assertTrue($result);
    }

    private function createSUT(MachineryFuelRepository $repository, Locale $locale): RegisterMachineryFuelService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterMachineryFuelService(
            machineryFuelRepository: $repository,
            authorizer: new MachineryFuelAuthorizer(),
        );
    }
}
