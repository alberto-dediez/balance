<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CateringItem\List;

use App\CarbonFootprint\Application\CateringItem\CateringItemAuthorizer;
use App\CarbonFootprint\Application\CateringItem\List\ListCateringItemsRequest;
use App\CarbonFootprint\Application\CateringItem\List\ListCateringItemsService;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListCateringItemsServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = CateringItemRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(CateringItemMother::random());
        $repository->add(CateringItemMother::random());
        $repository->add(CateringItemMother::random());
        $repository->add(CateringItemMother::random());

        $request = new ListCateringItemsRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->cateringItems);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByName(): void
    {
        $repository = CateringItemRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(CateringItemMother::fromName('name-1'));
        $repository->add(CateringItemMother::fromName('name-2'));
        $repository->add(CateringItemMother::fromName('name-2'));
        $repository->add(CateringItemMother::fromName('name-2'));

        $request = new ListCateringItemsRequest(name: 'name-1');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->cateringItems);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListCateringItemsRequest(name: 'name-2');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->cateringItems);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(CateringItemRepository $repository): ListCateringItemsService
    {
        return new ListCateringItemsService($repository, new CateringItemAuthorizer());
    }
}
