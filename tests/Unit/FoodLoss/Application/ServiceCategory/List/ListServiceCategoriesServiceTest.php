<?php

namespace App\Tests\Unit\FoodLoss\Application\ServiceCategory\List;

use PHPUnit\Framework\TestCase;
use App\FoodLoss\Application\ServiceCategory\ServiceCategoryAuthorizer;
use App\FoodLoss\Application\ServiceCategory\List\ListServiceCategoriesRequest;
use App\FoodLoss\Application\ServiceCategory\List\ListServiceCategoriesService;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryMother;
use App\FoodLoss\Application\ServiceCategory\List\ListServiceCategoriesResponse;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepositoryMother;

class ListServiceCategoriesServiceTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testInvoke()
    {
        $repository = ServiceCategoryRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(ServiceCategoryMother::random());
        $repository->add(ServiceCategoryMother::random());
        $repository->add(ServiceCategoryMother::random());
        $repository->add(ServiceCategoryMother::random());

        $request = new ListServiceCategoriesRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListServiceCategoriesResponse);
        $this->assertCount(4, $result->serviceCategories);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByName()
    {
        $repository = ServiceCategoryRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(ServiceCategoryMother::fromName('name-1'));
        $repository->add(ServiceCategoryMother::fromName('name-2'));
        $repository->add(ServiceCategoryMother::fromName('name-2'));
        $repository->add(ServiceCategoryMother::fromName('name-2'));

        $request = new ListServiceCategoriesRequest(
            name: 'name-1'
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListServiceCategoriesResponse);
        $this->assertCount(1, $result->serviceCategories);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListServiceCategoriesRequest(
            name: 'name-2'
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListServiceCategoriesResponse);
        $this->assertCount(3, $result->serviceCategories);
        $this->assertEquals(3, $result->totalResults);

        $request = new ListServiceCategoriesRequest(
            name: null
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListServiceCategoriesResponse);
        $this->assertCount(4, $result->serviceCategories);
        $this->assertEquals(4, $result->totalResults);
    }

    private function createSUT($repository)
    {
        return new ListServiceCategoriesService($repository, new ServiceCategoryAuthorizer());
    }
}