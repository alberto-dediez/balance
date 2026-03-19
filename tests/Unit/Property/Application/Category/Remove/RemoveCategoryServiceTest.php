<?php

namespace App\Tests\Unit\Property\Application\Category\Remove;

use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCategoryRepository;
use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Property\Domain\Model\Category\CategoryRemoved;
use App\Property\Application\Category\CategoryAuthorizer;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Property\Domain\Model\Category\CategoryMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Property\Application\Category\Remove\RemoveCategoryRequest;
use App\Property\Application\Category\Remove\RemoveCategoryService;
use App\Property\Application\Category\Remove\RemoveCategoryResponse;
use App\Tests\Unit\Property\Domain\Model\Category\CategoryRepositoryMother;

class RemoveCategoryServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = CategoryRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $sut = $this->createSUT($repository);

        $category = CategoryMother::random();

        $repository->add($category);

        $request = new RemoveCategoryRequest(
            categoryId: $category->id()
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RemoveCategoryResponse);
        $this->assertTrue($category->id()->equals($result->categoryId));
        
        $categoryPersisted = $repository->find($result->categoryId);

        $this->assertNull($categoryPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CategoryRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = CategoryRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $category = CategoryMother::random();

        $result = $sut->isHandled(new RemoveCategoryRequest(
            categoryId: $category->id()
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryCategoryRepository $repository): RemoveCategoryService
    {
        return new RemoveCategoryService($repository, new CategoryAuthorizer());
    }
}