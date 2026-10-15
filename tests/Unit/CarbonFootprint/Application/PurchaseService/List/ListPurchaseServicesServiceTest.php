<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PurchaseService\List;

use App\CarbonFootprint\Application\PurchaseService\List\ListPurchaseServicesRequest;
use App\CarbonFootprint\Application\PurchaseService\List\ListPurchaseServicesService;
use App\CarbonFootprint\Application\PurchaseService\PurchaseServiceAuthorizer;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListPurchaseServicesServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = PurchaseServiceRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(PurchaseServiceMother::random());
        $repository->add(PurchaseServiceMother::random());
        $repository->add(PurchaseServiceMother::random());
        $repository->add(PurchaseServiceMother::random());

        $request = new ListPurchaseServicesRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->purchaseServices);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByName(): void
    {
        $repository = PurchaseServiceRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(PurchaseServiceMother::fromName('name-1'));
        $repository->add(PurchaseServiceMother::fromName('name-2'));
        $repository->add(PurchaseServiceMother::fromName('name-2'));
        $repository->add(PurchaseServiceMother::fromName('name-2'));

        $request = new ListPurchaseServicesRequest(name: 'name-1');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->purchaseServices);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListPurchaseServicesRequest(name: 'name-2');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->purchaseServices);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(PurchaseServiceRepository $repository): ListPurchaseServicesService
    {
        return new ListPurchaseServicesService($repository, new PurchaseServiceAuthorizer());
    }
}
