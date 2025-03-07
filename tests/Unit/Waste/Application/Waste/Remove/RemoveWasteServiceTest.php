<?php

namespace App\Tests\Unit\Waste\Application\Waste\Remove;

use PHPUnit\Framework\TestCase;
use App\Waste\Domain\Model\Waste\WasteRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteMother;
use App\Waste\Application\Waste\Remove\RemoveWasteRequest;
use App\Waste\Application\Waste\Remove\RemoveWasteService;
use App\Waste\Application\Waste\Remove\RemoveWasteResponse;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Waste\Infrastructure\Repository\Waste\InMemoryWasteRepository;

class RemoveWasteServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke()
    {
        $repository = new InMemoryWasteRepository();

        $sut = $this->createSUT($repository);

        $waste = WasteMother::registeredWaste();

        $repository->add($waste);

        $request = new RemoveWasteRequest(
            wasteId: $waste->id()
        );

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RemoveWasteResponse);
        $this->assertTrue($waste->id()->equals($result->wasteId));
        
        $wastePersisted = $repository->find($result->wasteId);

        $this->assertNull($wastePersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof WasteRemoved);
    }

    private function createSUT($repository)
    {
        return new RemoveWasteService($repository);
    }
}