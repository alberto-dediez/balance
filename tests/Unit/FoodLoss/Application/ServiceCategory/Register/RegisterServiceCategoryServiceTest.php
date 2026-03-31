<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\ServiceCategory\Register;

use App\FoodLoss\Application\ServiceCategory\Register\RegisterServiceCategoryRequest;
use App\FoodLoss\Application\ServiceCategory\Register\RegisterServiceCategoryService;
use App\FoodLoss\Application\ServiceCategory\ServiceCategoryAuthorizer;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRegistered;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryTranslated;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterServiceCategoryServiceTest extends TestCase
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

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterServiceCategoryRequest(
            name: 'Service category 1',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->serviceCategoryId);
        $this->assertEquals(36, strlen((string) $result->serviceCategoryId));
        $this->assertEquals('Service category 1', $result->name);

        $serviceCategoryPersisted = $repository->find($result->serviceCategoryId);

        $this->assertNotNull($serviceCategoryPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ServiceCategoryRegistered);
        $this->assertEquals('Service category 1', $lastEvent->name());
        $this->assertTrue($serviceCategoryPersisted->id()->equals($lastEvent->serviceCategoryId()));
    }

    public function testRegisterWithLocaleTranslate(): void
    {
        $repository = ServiceCategoryRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new RegisterServiceCategoryRequest(
            name: 'changedName',
        );

        $result = $sut($request, UserMother::adminUser());

        $serviceCategoryPersisted = $repository->find($result->serviceCategoryId);

        $this->assertNotNull($serviceCategoryPersisted);
        $this->assertEquals('changedName', $serviceCategoryPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ServiceCategoryTranslated);
        $this->assertEquals('changedName', $lastEvent->name());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($serviceCategoryPersisted->id()->equals($lastEvent->serviceCategoryId()));
    }

    public function testIsHandle(): void
    {
        $repository = ServiceCategoryRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut->isHandled(new RegisterServiceCategoryRequest(
            name: 'changedName',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(ServiceCategoryRepository $repository, Locale $locale): RegisterServiceCategoryService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterServiceCategoryService(
            serviceCategoryRepository: $repository,
            authorizer: new ServiceCategoryAuthorizer(),
        );
    }
}
