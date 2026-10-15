<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CapitalGood\List;

use App\CarbonFootprint\Application\CapitalGood\CapitalGoodAuthorizer;
use App\CarbonFootprint\Application\CapitalGood\List\ListCapitalGoodsRequest;
use App\CarbonFootprint\Application\CapitalGood\List\ListCapitalGoodsService;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListCapitalGoodsServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = CapitalGoodRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(CapitalGoodMother::random());
        $repository->add(CapitalGoodMother::random());
        $repository->add(CapitalGoodMother::random());
        $repository->add(CapitalGoodMother::random());

        $request = new ListCapitalGoodsRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->capitalGoods);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByName(): void
    {
        $repository = CapitalGoodRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(CapitalGoodMother::fromName('name-1'));
        $repository->add(CapitalGoodMother::fromName('name-2'));
        $repository->add(CapitalGoodMother::fromName('name-2'));
        $repository->add(CapitalGoodMother::fromName('name-2'));

        $request = new ListCapitalGoodsRequest(name: 'name-1');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->capitalGoods);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListCapitalGoodsRequest(name: 'name-2');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->capitalGoods);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(CapitalGoodRepository $repository): ListCapitalGoodsService
    {
        return new ListCapitalGoodsService($repository, new CapitalGoodAuthorizer());
    }
}
