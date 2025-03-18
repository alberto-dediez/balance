<?php

namespace App\Tests\Unit\Waste\Application\Measurement\List;

use PHPUnit\Framework\TestCase;
use App\Waste\Application\Measurement\List\ListMeasurementsRequest;
use App\Waste\Application\Measurement\List\ListMeasurementsService;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementMother;
use App\Waste\Application\Measurement\List\ListMeasurementsResponse;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryMother;

class ListMeasurementsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testInvoke()
    {
        $repository = MeasurementRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(MeasurementMother::random());
        $repository->add(MeasurementMother::random());
        $repository->add(MeasurementMother::random());
        $repository->add(MeasurementMother::random());

        $request = new ListMeasurementsRequest();

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListMeasurementsResponse);
        $this->assertCount(4, $result->measurements);
        $this->assertEquals(4, $result->totalResults);
    }

    private function createSUT($repository)
    {
        return new ListMeasurementsService($repository);
    }
}