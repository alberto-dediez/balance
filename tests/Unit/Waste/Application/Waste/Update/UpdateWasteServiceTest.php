<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Waste\Update;

use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleGetter;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteMother;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteRepositoryMother;
use App\Waste\Application\Waste\Update\UpdateWasteRequest;
use App\Waste\Application\Waste\Update\UpdateWasteResponse;
use App\Waste\Application\Waste\Update\UpdateWasteService;
use App\Waste\Application\Waste\WasteAuthorizer;
use App\Waste\Domain\Model\Waste\Waste;
use App\Waste\Domain\Model\Waste\WasteTranslated;
use App\Waste\Domain\Model\Waste\WasteUpdated;
use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryWasteRepository;
use PHPUnit\Framework\TestCase;

class UpdateWasteServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
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

    public function testUpdateWithLocaleTranslate(): void
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

    public function testIsHandle(): void
    {
        $repository = WasteRepositoryMother::inMemory();

        $sut = $this->createSUT($repository, Locale::English);

        $waste = WasteMother::random();

        $result = $sut->isHandled(new UpdateWasteRequest(
            wasteId: $waste->id(),
            name: 'changedName',
            isDangerous: false,
            density: '0.112',
            color: '#fff333',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryWasteRepository $repository, Locale $locale): UpdateWasteService
    {
        return new UpdateWasteService(
            wasteRepository: $repository,
            localeGetter: new FakeLocaleGetter(
                locale: $locale,
            ),
            authorizer: new WasteAuthorizer(),
        );
    }
}
