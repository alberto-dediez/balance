<?php

namespace App\Tests\Unit\Property\Application\Category\List;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Property\Application\Category\CategoryAuthorizer;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Property\Domain\Model\Category\CategoryMother;
use App\Property\Application\Category\List\ListCategoriesRequest;
use App\Property\Application\Category\List\ListCategoriesService;
use App\Property\Application\Category\List\ListCategoriesResponse;
use App\Tests\Unit\Property\Domain\Model\Category\CategoryRepositoryMother;

class ListCategoriesServiceTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testInvoke()
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

    private function createSUT($repository)
    {
        return new ListCategoriesService($repository, new CategoryAuthorizer());
    }
}