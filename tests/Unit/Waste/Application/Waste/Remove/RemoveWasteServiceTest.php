<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\Waste\Remove;

use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteMother;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteRepositoryMother;
use App\Waste\Application\Waste\Remove\RemoveWasteRequest;
use App\Waste\Application\Waste\Remove\RemoveWasteResponse;
use App\Waste\Application\Waste\Remove\RemoveWasteService;
use App\Waste\Application\Waste\WasteAuthorizer;
use App\Waste\Domain\Model\Waste\WasteRemoved;
use App\Waste\Infrastructure\Persistence\InMemory\Repository\InMemoryWasteRepository;
use PHPUnit\Framework\TestCase;

class RemoveWasteServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = WasteRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $waste = WasteMother::registeredWaste();

        $repository->add($waste);

        $request = new RemoveWasteRequest(
            wasteId: $waste->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RemoveWasteResponse);
        $this->assertTrue($waste->id()->equals($result->wasteId));

        $wastePersisted = $repository->find($result->wasteId);

        $this->assertNull($wastePersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof WasteRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = WasteRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $waste = WasteMother::random();

        $result = $sut->isHandled(new RemoveWasteRequest(
            wasteId: $waste->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryWasteRepository $repository): RemoveWasteService
    {
        return new RemoveWasteService($repository, new WasteAuthorizer());
    }
}
