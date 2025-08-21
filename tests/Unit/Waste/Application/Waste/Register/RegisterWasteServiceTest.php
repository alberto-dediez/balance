<?php

namespace App\Tests\Unit\Waste\Application\Waste\Register;

use PHPUnit\Framework\TestCase;
use App\Waste\Domain\Model\Waste\Waste;
use App\Shared\Domain\Model\Locale\Locale;
use App\Waste\Application\Waste\WasteAuthorizer;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Waste\Domain\Model\Waste\WasteRegistered;
use App\Waste\Domain\Model\Waste\WasteTranslated;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Waste\Application\Waste\Register\RegisterWasteRequest;
use App\Waste\Application\Waste\Register\RegisterWasteService;
use App\Waste\Application\Waste\Register\RegisterWasteResponse;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleGetter;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteRepositoryMother;

class RegisterWasteServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase()
    {
        $repository = WasteRepositoryMother::inMemory();

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterWasteRequest(
            name: 'Company ABC',
            isDangerous: true,
            density: '0.03',
            color: '#cccddd'
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

    public function testRegisterWithLocaleTranslate()
    {
        $repository = WasteRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new RegisterWasteRequest(
            name: 'changedName',
            isDangerous: true,
            density: '0.03',
            color: '#cccddd'
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

    public function testIsHandle()
    {
        $repository = WasteRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut->isHandled(new RegisterWasteRequest(
            name: 'changedName',
            isDangerous: true,
            density: '0.03',
            color: '#cccddd'
        ));

        $this->assertTrue($result);
    }

    private function createSUT($repository, Locale $locale)
    {
        return new RegisterWasteService(
            wasteRepository: $repository, 
            localeGetter: new FakeLocaleGetter(
                locale: $locale
            ),
            authorizer: new WasteAuthorizer()
        );
    }
}