<?php

namespace App\Tests\Unit\FoodLoss\Application\ServiceCategory\Update;

use PHPUnit\Framework\TestCase;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategory;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Model\Locale\Locale;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryUpdated;
use App\FoodLoss\Application\ServiceCategory\ServiceCategoryAuthorizer;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryTranslated;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\FoodLoss\Application\ServiceCategory\Update\UpdateServiceCategoryRequest;
use App\FoodLoss\Application\ServiceCategory\Update\UpdateServiceCategoryService;
use App\FoodLoss\Application\ServiceCategory\Update\UpdateServiceCategoryResponse;
use App\FoodLoss\Domain\Model\ServiceCategory\Priority;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleGetter;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepositoryMother;

class UpdateServiceCategoryServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = ServiceCategoryRepositoryMother::inMemory();

        $serviceCategory = ServiceCategoryMother::random();

        $repository->add($serviceCategory);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateServiceCategoryRequest(
            serviceCategoryId: $serviceCategory->id(),
            name: 'changedName'
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof UpdateServiceCategoryResponse);

        $serviceCategoryPersisted = $repository->find($result->serviceCategoryId);

        $this->assertNotNull($serviceCategoryPersisted);
        $this->assertTrue($serviceCategoryPersisted instanceof ServiceCategory);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ServiceCategoryUpdated);
        $this->assertEquals('changedName', $lastEvent->name());
        $this->assertTrue($serviceCategoryPersisted->id()->equals($lastEvent->serviceCategoryId()));
    }

    public function testUpdateWithLocaleTranslate(): void
    {
        $repository = ServiceCategoryRepositoryMother::inMemory(Locale::Spanish);

        $serviceCategory = ServiceCategoryMother::random();

        $repository->add($serviceCategory);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new UpdateServiceCategoryRequest(
            serviceCategoryId: $serviceCategory->id(),
            name: 'changedName'
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof UpdateServiceCategoryResponse);

        $serviceCategoryPersisted = $repository->find($result->serviceCategoryId);

        $this->assertNotNull($serviceCategoryPersisted);
        $this->assertTrue($serviceCategoryPersisted instanceof ServiceCategory);
        $this->assertNotEquals('changedName', $serviceCategoryPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ServiceCategoryTranslated);
        $this->assertEquals('changedName', $lastEvent->name());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($serviceCategoryPersisted->id()->equals($lastEvent->serviceCategoryId()));
    }

    public function testIsHandle(): void
    {
        $repository = ServiceCategoryRepositoryMother::inMemory();

        $sut = $this->createSUT($repository, Locale::English);

        $serviceCategory = ServiceCategoryMother::random();

        $result = $sut->isHandled(new UpdateServiceCategoryRequest(
            serviceCategoryId: $serviceCategory->id(),
            name: 'changedName'
        ));

        $this->assertTrue($result);
    }

    private function createSUT($repository, Locale $locale): UpdateServiceCategoryService
    {
        return new UpdateServiceCategoryService(
            serviceCategoryRepository: $repository,
            localeGetter: new FakeLocaleGetter(
                locale: $locale
            ),
            authorizer: new ServiceCategoryAuthorizer()
        );
    }
}