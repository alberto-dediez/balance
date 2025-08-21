<?php

namespace App\Tests\Unit\Property\Application\Category\Register;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Property\Domain\Model\Category\Category;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Property\Application\Category\CategoryAuthorizer;
use App\Property\Domain\Model\Category\CategoryRegistered;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Property\Application\Category\Register\RegisterCategoryRequest;
use App\Property\Application\Category\Register\RegisterCategoryService;
use App\Property\Application\Category\Register\RegisterCategoryResponse;
use App\Tests\Unit\Property\Domain\Model\Category\CategoryRepositoryMother;

class RegisterCategoryServiceTest extends TestCase
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

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository);

        $request = new RegisterCategoryRequest(
            name: '1 Estrella'
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RegisterCategoryResponse);
        $this->assertNotEmpty((string) $result->categoryId);
        $this->assertEquals(36, strlen((string) $result->categoryId));

        $categoryPersisted = $repository->find($result->categoryId);

        $this->assertNotNull($categoryPersisted);
        $this->assertTrue($categoryPersisted instanceof Category);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CategoryRegistered);
        $this->assertEquals('1 Estrella', $lastEvent->name());
        $this->assertTrue($categoryPersisted->id()->equals($lastEvent->categoryId()));
    }

    public function testIsHandle()
    {
        $repository = CategoryRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterCategoryRequest(
            name: '2 stars'
        ));

        $this->assertTrue($result);
    }

    private function createSUT($repository)
    {
        return new RegisterCategoryService($repository, new CategoryAuthorizer());
    }
}