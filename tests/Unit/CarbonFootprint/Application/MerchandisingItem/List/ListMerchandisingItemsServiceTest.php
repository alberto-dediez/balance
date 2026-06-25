<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MerchandisingItem\List;

use App\CarbonFootprint\Application\MerchandisingItem\List\ListMerchandisingItemsRequest;
use App\CarbonFootprint\Application\MerchandisingItem\List\ListMerchandisingItemsService;
use App\CarbonFootprint\Application\MerchandisingItem\MerchandisingItemAuthorizer;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListMerchandisingItemsServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = MerchandisingItemRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(MerchandisingItemMother::random());
        $repository->add(MerchandisingItemMother::random());
        $repository->add(MerchandisingItemMother::random());
        $repository->add(MerchandisingItemMother::random());

        $request = new ListMerchandisingItemsRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->merchandisingItems);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByName(): void
    {
        $repository = MerchandisingItemRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(MerchandisingItemMother::fromName('name-1'));
        $repository->add(MerchandisingItemMother::fromName('name-2'));
        $repository->add(MerchandisingItemMother::fromName('name-2'));
        $repository->add(MerchandisingItemMother::fromName('name-2'));

        $request = new ListMerchandisingItemsRequest(name: 'name-1');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->merchandisingItems);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListMerchandisingItemsRequest(name: 'name-2');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->merchandisingItems);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(MerchandisingItemRepository $repository): ListMerchandisingItemsService
    {
        return new ListMerchandisingItemsService($repository, new MerchandisingItemAuthorizer());
    }
}
