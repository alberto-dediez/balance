<?php

namespace App\Tests\Unit\Waste\Application\Waste\List;

use PHPUnit\Framework\TestCase;
use App\Waste\Application\Waste\List\ListWastesRequest;
use App\Waste\Application\Waste\List\ListWastesService;
use App\Waste\Application\Waste\List\ListWastesResponse;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteMother;
use App\Waste\Infrastructure\Repository\Waste\InMemoryWasteRepository;

class ListWastesServiceTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testInvoke()
    {
        $repository = new InMemoryWasteRepository();

        $sut = $this->createSUT($repository);

        $repository->add(WasteMother::random());
        $repository->add(WasteMother::random());
        $repository->add(WasteMother::random());
        $repository->add(WasteMother::random());

        $request = new ListWastesRequest();

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListWastesResponse);
        $this->assertCount(4, $result->wastes);
        $this->assertEquals(4, $result->totalResults);
    }

    private function createSUT($repository)
    {
        return new ListWastesService($repository);
    }
}