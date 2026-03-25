<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Waste\Register;

use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteRepositoryMother;
use App\Waste\Application\Waste\Register\RegisterWasteRequest;
use App\Waste\Application\Waste\Register\RegisterWasteResponse;
use App\Waste\Application\Waste\Register\RegisterWasteService;
use App\Waste\Application\Waste\WasteAuthorizer;
use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Waste\WasteRegistered;
use App\Waste\Domain\Model\Waste\WasteTranslated;
use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryWasteRepository;
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
        $repository = WasteRepositoryMother::inMemory();

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterWasteRequest(
            name: 'Company ABC',
            isDangerous: true,
            density: '0.03',
            color: '#cccddd',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RegisterWasteResponse);
        $this->assertNotEmpty((string) $result->wasteId);
        $this->assertEquals(36, strlen((string) $result->wasteId));
        $this->assertTrue($result->isDangerous);

        $wastePersisted = $repository->find($result->wasteId);

        $this->assertNotNull($wastePersisted);
        $this->assertTrue($wastePersisted instanceof Waste);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof WasteRegistered);
        $this->assertEquals('Company ABC', $lastEvent->name());
        $this->assertEquals('0.03', $lastEvent->density());
        $this->assertEquals('#cccddd', $lastEvent->color());
        $this->assertTrue($wastePersisted->id()->equals($lastEvent->wasteId()));
        $this->assertTrue($wastePersisted->isDangerous());
    }

    public function testRegisterWithLocaleTranslate(): void
    {
        $repository = WasteRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new RegisterWasteRequest(
            name: 'changedName',
            isDangerous: true,
            density: '0.03',
            color: '#cccddd',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RegisterWasteResponse);

        $wastePersisted = $repository->find($result->wasteId);

        $this->assertNotNull($wastePersisted);
        $this->assertTrue($wastePersisted instanceof Waste);
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
            name: 'changedName',
            isDangerous: true,
            density: '0.03',
            color: '#cccddd',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryWasteRepository $repository, Locale $locale): RegisterWasteService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterWasteService(
            wasteRepository: $repository,
            authorizer: new WasteAuthorizer(),
        );
    }
}
