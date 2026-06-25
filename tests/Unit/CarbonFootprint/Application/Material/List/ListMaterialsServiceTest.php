<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Material\List;

use App\CarbonFootprint\Application\Material\List\ListMaterialsRequest;
use App\CarbonFootprint\Application\Material\List\ListMaterialsService;
use App\CarbonFootprint\Application\Material\MaterialAuthorizer;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListMaterialsServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = MaterialRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(MaterialMother::random());
        $repository->add(MaterialMother::random());
        $repository->add(MaterialMother::random());
        $repository->add(MaterialMother::random());

        $request = new ListMaterialsRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->materials);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByName(): void
    {
        $repository = MaterialRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(MaterialMother::fromName('name-1'));
        $repository->add(MaterialMother::fromName('name-2'));
        $repository->add(MaterialMother::fromName('name-2'));
        $repository->add(MaterialMother::fromName('name-2'));

        $request = new ListMaterialsRequest(name: 'name-1');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->materials);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListMaterialsRequest(name: 'name-2');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->materials);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(MaterialRepository $repository): ListMaterialsService
    {
        return new ListMaterialsService($repository, new MaterialAuthorizer());
    }
}
