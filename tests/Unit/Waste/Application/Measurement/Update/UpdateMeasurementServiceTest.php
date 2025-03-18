<?php

namespace App\Tests\Unit\Waste\Application\Measurement\Update;

use PHPUnit\Framework\TestCase;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\Measurement\Purpose;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Waste\Domain\Model\Measurement\MeasurementUpdated;
use App\Waste\Domain\Model\Measurement\MeasurementMethod;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementMother;
use App\Waste\Application\Measurement\Update\UpdateMeasurementRequest;
use App\Waste\Application\Measurement\Update\UpdateMeasurementService;
use App\Waste\Application\Measurement\Update\UpdateMeasurementResponse;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryMother;

class UpdateMeasurementServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase()
    {
        $repository = MeasurementRepositoryMother::inMemory();

        $measurement = MeasurementMother::random();

        $repository->add($measurement);

        $sut = $this->createSUT($repository);

        $request = new UpdateMeasurementRequest(
            measurementId: $measurement->id(),
            quantity: 2,
            load: 0.26,
            justification: null
        );

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof UpdateMeasurementResponse);

        $measurementPersisted = $repository->find($result->measurementId);

        $this->assertNotNull($measurementPersisted);
        $this->assertTrue($measurementPersisted instanceof Measurement);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MeasurementUpdated);
        $this->assertEquals('2', $lastEvent->quantity());
        $this->assertTrue($measurementPersisted->id()->equals($lastEvent->measurementId()));
    }

    private function createSUT($repository)
    {
        return new UpdateMeasurementService(
            measurementRepository: $repository
        );
    }
}