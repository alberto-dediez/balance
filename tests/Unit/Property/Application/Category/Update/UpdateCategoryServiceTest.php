<?php

namespace App\Tests\Unit\Property\Application\Category\Update;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Property\Domain\Model\Category\Category;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Property\Domain\Model\Category\CategoryUpdated;
use App\Property\Application\Category\CategoryAuthorizer;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Property\Domain\Model\Category\CategoryMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Property\Application\Category\Update\UpdateCategoryRequest;
use App\Property\Application\Category\Update\UpdateCategoryService;
use App\Property\Application\Category\Update\UpdateCategoryResponse;
use App\Tests\Unit\Property\Domain\Model\Category\CategoryRepositoryMother;

class UpdateCategoryServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase()
    {
        $repository = CategoryRepositoryMother::inMemory();

        $category = CategoryMother::fromName('MyCategory');

        $repository->add($category);

        $sut = $this->createSUT($repository);

        $request = new UpdateCategoryRequest(
            categoryId: $category->id(),
            name: '1 Estrella'
        );

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof UpdateCategoryResponse);

        $categoryPersisted = $repository->find($result->categoryId);

        $this->assertNotNull($categoryPersisted);
        $this->assertTrue($categoryPersisted instanceof Category);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CategoryUpdated);
        $this->assertEquals('1 Estrella', $lastEvent->name());
        $this->assertTrue($categoryPersisted->id()->equals($lastEvent->categoryId()));
    }

    public function testIsHandle()
    {
        $repository = CategoryRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $category = CategoryMother::random();

        $result = $sut->isHandled(new UpdateCategoryRequest(
            categoryId: $category->id(),
            name: '***'
        ));

        $this->assertTrue($result);
    }

    private function createSUT($repository)
    {
        return new UpdateCategoryService($repository, new CategoryAuthorizer());
    }
}