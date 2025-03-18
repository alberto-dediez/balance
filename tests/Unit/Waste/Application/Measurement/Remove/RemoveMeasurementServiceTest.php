<?php

namespace App\Tests\Unit\Waste\Application\Measurement\Remove;

use PHPUnit\Framework\TestCase;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Waste\Domain\Model\Measurement\MeasurementRemoved;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementMother;
use App\Waste\Application\Measurement\Remove\RemoveMeasurementRequest;
use App\Waste\Application\Measurement\Remove\RemoveMeasurementService;
use App\Waste\Application\Measurement\Remove\RemoveMeasurementResponse;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryMother;

class RemoveMeasurementServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke()
    {
        $repository = MeasurementRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $measurement = MeasurementMother::random();

        $repository->add($measurement);

        $request = new RemoveMeasurementRequest(
            measurementId: $measurement->id()
        );

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RemoveMeasurementResponse);
        $this->assertTrue($measurement->id()->equals($result->measurementId));
        
        $measurementPersisted = $repository->find($result->measurementId);

        $this->assertNull($measurementPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MeasurementRemoved);
    }

    private function createSUT($repository)
    {
        return new RemoveMeasurementService($repository);
    }
}