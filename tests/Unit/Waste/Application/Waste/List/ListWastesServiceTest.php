<?php

namespace App\Tests\Unit\Waste\Application\Waste\List;

use PHPUnit\Framework\TestCase;
use App\Waste\Application\Waste\WasteAuthorizer;
use App\Waste\Application\Waste\List\ListWastesRequest;
use App\Waste\Application\Waste\List\ListWastesService;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteMother;
use App\Waste\Application\Waste\List\ListWastesResponse;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteRepositoryMother;

class ListWastesServiceTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testInvoke()
    {
        $repository = WasteRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(WasteMother::random());
        $repository->add(WasteMother::random());
        $repository->add(WasteMother::random());
        $repository->add(WasteMother::random());

        $request = new ListWastesRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListWastesResponse);
        $this->assertCount(4, $result->wastes);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByIsDangerous()
    {
        $repository = WasteRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(WasteMother::fromIsDangerous(true));
        $repository->add(WasteMother::fromIsDangerous(false));
        $repository->add(WasteMother::fromIsDangerous(false));
        $repository->add(WasteMother::fromIsDangerous(false));

        $request = new ListWastesRequest(
            isDangerous: true
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListWastesResponse);
        $this->assertCount(1, $result->wastes);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListWastesRequest(
            isDangerous: false
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListWastesResponse);
        $this->assertCount(3, $result->wastes);
        $this->assertEquals(3, $result->totalResults);

        $request = new ListWastesRequest(
            isDangerous: null
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListWastesResponse);
        $this->assertCount(4, $result->wastes);
        $this->assertEquals(4, $result->totalResults);
    }

    private function createSUT($repository)
    {
        return new ListWastesService($repository, new WasteAuthorizer());
    }
}