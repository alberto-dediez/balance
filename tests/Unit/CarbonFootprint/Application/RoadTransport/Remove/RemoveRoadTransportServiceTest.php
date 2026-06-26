<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\RoadTransport\Remove;

use App\CarbonFootprint\Application\RoadTransport\Remove\RemoveRoadTransportRequest;
use App\CarbonFootprint\Application\RoadTransport\Remove\RemoveRoadTransportService;
use App\CarbonFootprint\Application\RoadTransport\RoadTransportAuthorizer;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRemoved;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveRoadTransportServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $roadTransport = RoadTransportMother::registeredRoadTransport();

        $repository->add($roadTransport);

        $request = new RemoveRoadTransportRequest(
            roadTransportId: $roadTransport->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($roadTransport->id()->equals($result->roadTransportId));

        $roadTransportPersisted = $repository->find($result->roadTransportId);

        $this->assertNull($roadTransportPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof RoadTransportRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $roadTransport = RoadTransportMother::random();

        $result = $sut->isHandled(new RemoveRoadTransportRequest(
            roadTransportId: $roadTransport->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(RoadTransportRepository $repository): RemoveRoadTransportService
    {
        return new RemoveRoadTransportService($repository, new RoadTransportAuthorizer());
    }
}
