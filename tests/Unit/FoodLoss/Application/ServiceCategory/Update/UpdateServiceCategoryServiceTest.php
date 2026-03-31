<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\ServiceCategory\Update;

use App\FoodLoss\Application\ServiceCategory\ServiceCategoryAuthorizer;
use App\FoodLoss\Application\ServiceCategory\Update\UpdateServiceCategoryRequest;
use App\FoodLoss\Application\ServiceCategory\Update\UpdateServiceCategoryService;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryTranslated;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryUpdated;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryMother;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateServiceCategoryServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();

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
            name: 'changedName',
        );

        $result = $sut($request, $user);

        $serviceCategoryPersisted = $repository->find($result->serviceCategoryId);

        $this->assertNotNull($serviceCategoryPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ServiceCategoryUpdated);
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
            name: 'changedName',
        );

        $result = $sut($request, $user);

        $serviceCategoryPersisted = $repository->find($result->serviceCategoryId);

        FakeLocaleSetter::set(Locale::Spanish);

        $this->assertNotNull($serviceCategoryPersisted);
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
            name: 'changedName',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(ServiceCategoryRepository $repository, Locale $locale): UpdateServiceCategoryService
    {
        FakeLocaleSetter::set($locale);

        return new UpdateServiceCategoryService(
            serviceCategoryRepository: $repository,
            authorizer: new ServiceCategoryAuthorizer(),
        );
    }
}
