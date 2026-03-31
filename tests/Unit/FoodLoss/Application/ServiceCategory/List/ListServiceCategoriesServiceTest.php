<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\ServiceCategory\List;

use App\FoodLoss\Application\ServiceCategory\List\ListServiceCategoriesRequest;
use App\FoodLoss\Application\ServiceCategory\List\ListServiceCategoriesService;
use App\FoodLoss\Application\ServiceCategory\ServiceCategoryAuthorizer;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListServiceCategoriesServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = ServiceCategoryRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(ServiceCategoryMother::random());
        $repository->add(ServiceCategoryMother::random());
        $repository->add(ServiceCategoryMother::random());
        $repository->add(ServiceCategoryMother::random());

        $request = new ListServiceCategoriesRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->serviceCategories);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByName(): void
    {
        $repository = ServiceCategoryRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(ServiceCategoryMother::fromName('name-1'));
        $repository->add(ServiceCategoryMother::fromName('name-2'));
        $repository->add(ServiceCategoryMother::fromName('name-2'));
        $repository->add(ServiceCategoryMother::fromName('name-2'));

        $request = new ListServiceCategoriesRequest(
            name: 'name-1',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->serviceCategories);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListServiceCategoriesRequest(
            name: 'name-2',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->serviceCategories);
        $this->assertEquals(3, $result->totalResults);

        $request = new ListServiceCategoriesRequest(
            name: null,
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->serviceCategories);
        $this->assertEquals(4, $result->totalResults);
    }

    private function createSUT(ServiceCategoryRepository $repository): ListServiceCategoriesService
    {
        return new ListServiceCategoriesService($repository, new ServiceCategoryAuthorizer());
    }
}
