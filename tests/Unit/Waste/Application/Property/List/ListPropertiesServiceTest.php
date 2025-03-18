<?php

namespace App\Tests\Unit\Waste\Application\Property\List;

use PHPUnit\Framework\TestCase;
use App\Tests\Unit\Waste\Domain\Model\Property\PropertyMother;
use App\Waste\Application\Property\List\ListPropertiesRequest;
use App\Waste\Application\Property\List\ListPropertiesService;
use App\Waste\Application\Property\List\ListPropertiesResponse;
use App\Tests\Unit\Waste\Domain\Model\Property\PropertyRepositoryMother;

class ListPropertiesServiceTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testInvoke()
    {
        $repository = PropertyRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());

        $request = new ListPropertiesRequest();

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListPropertiesResponse);
        $this->assertCount(4, $result->properties);
        $this->assertEquals(4, $result->totalResults);
    }

    private function createSUT($repository)
    {
        return new ListPropertiesService($repository);
    }
}