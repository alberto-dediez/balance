<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Waste\Register;

use App\CarbonFootprint\Application\Waste\Register\RegisterWasteRequest;
use App\CarbonFootprint\Application\Waste\Register\RegisterWasteService;
use App\CarbonFootprint\Application\Waste\WasteAuthorizer;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\Waste\WasteRegistered;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;
use App\CarbonFootprint\Domain\Model\Waste\WasteTranslated;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterWasteServiceTest extends TestCase
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
        $repository = WasteRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterWasteRequest(
            name: 'Waste 1',
            unit: Unit::Kilogram,
            density: 1.5,
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->wasteId);
        $this->assertEquals(36, strlen((string) $result->wasteId));
        $this->assertEquals('Waste 1', $result->name);

        $wastePersisted = $repository->find($result->wasteId);

        $this->assertNotNull($wastePersisted);
        $this->assertSame(1.5, $wastePersisted->density());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof WasteRegistered);
        $this->assertEquals('Waste 1', $lastEvent->name());
        $this->assertSame(1.5, $lastEvent->density());
        $this->assertTrue($wastePersisted->id()->equals($lastEvent->wasteId()));
    }

    public function testRegisterWithLocaleTranslate(): void
    {
        $repository = WasteRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new RegisterWasteRequest(
            name: 'changedName',
            unit: Unit::Piece,
            density: 1.0,
        );

        $result = $sut($request, UserMother::adminUser());

        $wastePersisted = $repository->find($result->wasteId);

        $this->assertNotNull($wastePersisted);
        $this->assertEquals('changedName', $wastePersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof WasteTranslated);
        $this->assertEquals('changedName', $lastEvent->name());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($wastePersisted->id()->equals($lastEvent->wasteId()));
    }

    public function testIsHandle(): void
    {
        $repository = WasteRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut->isHandled(new RegisterWasteRequest(
            name: 'test',
            unit: Unit::Kilogram,
            density: 1.0,
        ));

        $this->assertTrue($result);
    }

    private function createSUT(WasteRepository $repository, Locale $locale): RegisterWasteService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterWasteService(
            wasteRepository: $repository,
            authorizer: new WasteAuthorizer(),
        );
    }
}
