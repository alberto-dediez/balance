<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Category\List;

use App\Property\Application\Category\CategoryAuthorizer;
use App\Property\Application\Category\List\ListCategoriesRequest;
use App\Property\Application\Category\List\ListCategoriesResponse;
use App\Property\Application\Category\List\ListCategoriesService;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCategoryRepository;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Property\Domain\Model\Category\CategoryMother;
use App\Tests\Unit\Property\Domain\Model\Category\CategoryRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListCategoriesServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = CategoryRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository);

        $repository->add(CategoryMother::random());
        $repository->add(CategoryMother::random());
        $repository->add(CategoryMother::random());
        $repository->add(CategoryMother::random());

        $request = new ListCategoriesRequest();

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListCategoriesResponse);
        $this->assertCount(4, $result->categories);
        $this->assertEquals(4, $result->totalResults);
    }

    private function createSUT(InMemoryCategoryRepository $repository): ListCategoriesService
    {
        return new ListCategoriesService($repository, new CategoryAuthorizer());
    }
}
