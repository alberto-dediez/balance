<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Waste\List;

use App\CarbonFootprint\Application\Waste\List\ListWastesRequest;
use App\CarbonFootprint\Application\Waste\List\ListWastesService;
use App\CarbonFootprint\Application\Waste\WasteAuthorizer;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListWastesServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = WasteRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(WasteMother::random());
        $repository->add(WasteMother::random());
        $repository->add(WasteMother::random());
        $repository->add(WasteMother::random());

        $request = new ListWastesRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->wastes);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByName(): void
    {
        $repository = WasteRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(WasteMother::fromName('name-1'));
        $repository->add(WasteMother::fromName('name-2'));
        $repository->add(WasteMother::fromName('name-2'));
        $repository->add(WasteMother::fromName('name-2'));

        $request = new ListWastesRequest(name: 'name-1');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->wastes);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListWastesRequest(name: 'name-2');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->wastes);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(WasteRepository $repository): ListWastesService
    {
        return new ListWastesService($repository, new WasteAuthorizer());
    }
}
