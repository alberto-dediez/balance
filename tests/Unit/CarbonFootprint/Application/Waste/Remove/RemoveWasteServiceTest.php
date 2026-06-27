<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Waste\Remove;

use App\CarbonFootprint\Application\Waste\Remove\RemoveWasteRequest;
use App\CarbonFootprint\Application\Waste\Remove\RemoveWasteService;
use App\CarbonFootprint\Application\Waste\WasteAuthorizer;
use App\CarbonFootprint\Domain\Model\Waste\WasteRemoved;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
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

    private function createSUT(WasteRepository $repository): RemoveWasteService
    {
        return new RemoveWasteService($repository, new WasteAuthorizer());
    }
}
