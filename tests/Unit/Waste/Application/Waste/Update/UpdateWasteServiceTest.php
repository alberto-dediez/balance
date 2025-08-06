<?php

namespace App\Tests\Unit\Waste\Application\Waste\Update;

use PHPUnit\Framework\TestCase;
use App\Waste\Domain\Model\Waste\Waste;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Model\Locale\Locale;
use App\Waste\Domain\Model\Waste\WasteUpdated;
use App\Waste\Application\Waste\WasteAuthorizer;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Waste\Domain\Model\Waste\WasteTranslated;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Waste\Application\Waste\Update\UpdateWasteRequest;
use App\Waste\Application\Waste\Update\UpdateWasteService;
use App\Waste\Application\Waste\Update\UpdateWasteResponse;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleGetter;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteRepositoryMother;

class UpdateWasteServiceTest extends TestCase
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

        $waste = WasteMother::random();

        $repository->add($waste);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateWasteRequest(
            wasteId: $waste->id(),
            name: 'changedName',
            isDangerous: false,
            density: '0.112',
            color: '#fff333',
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof UpdateWasteResponse);

        $wastePersisted = $repository->find($result->wasteId);

        $this->assertNotNull($wastePersisted);
        $this->assertTrue($wastePersisted instanceof Waste);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof WasteUpdated);
        $this->assertEquals('0.112', $lastEvent->density());
        $this->assertTrue($wastePersisted->id()->equals($lastEvent->wasteId()));
    }

    public function testUpdateWithLocaleTranslate()
    {
        $repository = WasteRepositoryMother::inMemory(Locale::Spanish);

        $waste = WasteMother::random();

        $repository->add($waste);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new UpdateWasteRequest(
            wasteId: $waste->id(),
            name: 'changedName',
            isDangerous: false,
            density: '0.112',
            color: '#fff333',
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof UpdateWasteResponse);

        $wastePersisted = $repository->find($result->wasteId);

        $this->assertNotNull($wastePersisted);
        $this->assertTrue($wastePersisted instanceof Waste);
        $this->assertNotEquals('changedName', $wastePersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof WasteTranslated);
        $this->assertEquals('changedName', $lastEvent->name());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($wastePersisted->id()->equals($lastEvent->wasteId()));
    }

    private function createSUT($repository, Locale $locale)
    {
        return new UpdateWasteService(
            wasteRepository: $repository,
            localeGetter: new FakeLocaleGetter(
                locale: $locale
            ),
            authorizer: new WasteAuthorizer()
        );
    }
}