<?php

namespace App\Tests\Unit\FoodLoss\Application\ServiceCategory\Remove;

use PHPUnit\Framework\TestCase;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRemoved;
use App\FoodLoss\Application\ServiceCategory\ServiceCategoryAuthorizer;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\FoodLoss\Application\ServiceCategory\Remove\RemoveServiceCategoryRequest;
use App\FoodLoss\Application\ServiceCategory\Remove\RemoveServiceCategoryService;
use App\FoodLoss\Application\ServiceCategory\Remove\RemoveServiceCategoryResponse;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepositoryMother;

class RemoveServiceCategoryServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = ServiceCategoryRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $serviceCategory = ServiceCategoryMother::registeredServiceCategory();

        $repository->add($serviceCategory);

        $request = new RemoveServiceCategoryRequest(
            serviceCategoryId: $serviceCategory->id()
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RemoveServiceCategoryResponse);
        $this->assertTrue($serviceCategory->id()->equals($result->serviceCategoryId));
        
        $serviceCategoryPersisted = $repository->find($result->serviceCategoryId);

        $this->assertNull($serviceCategoryPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ServiceCategoryRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = ServiceCategoryRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $serviceCategory = ServiceCategoryMother::random();

        $result = $sut->isHandled(new RemoveServiceCategoryRequest(
            serviceCategoryId: $serviceCategory->id()
        ));

        $this->assertTrue($result);
    }

    private function createSUT($repository): RemoveServiceCategoryService
    {
        return new RemoveServiceCategoryService($repository, new ServiceCategoryAuthorizer());
    }
}