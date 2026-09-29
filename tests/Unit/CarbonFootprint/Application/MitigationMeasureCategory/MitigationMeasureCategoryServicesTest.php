<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MitigationMeasureCategory;

use App\CarbonFootprint\Application\MitigationMeasureCategory\List\ListMitigationMeasureCategoriesRequest;
use App\CarbonFootprint\Application\MitigationMeasureCategory\List\ListMitigationMeasureCategoriesService;
use App\CarbonFootprint\Application\MitigationMeasureCategory\MitigationMeasureCategoryAuthorizer;
use App\CarbonFootprint\Application\MitigationMeasureCategory\Register\RegisterMitigationMeasureCategoryRequest;
use App\CarbonFootprint\Application\MitigationMeasureCategory\Register\RegisterMitigationMeasureCategoryService;
use App\CarbonFootprint\Application\MitigationMeasureCategory\Remove\RemoveMitigationMeasureCategoryRequest;
use App\CarbonFootprint\Application\MitigationMeasureCategory\Remove\RemoveMitigationMeasureCategoryService;
use App\CarbonFootprint\Application\MitigationMeasureCategory\Update\UpdateMitigationMeasureCategoryRequest;
use App\CarbonFootprint\Application\MitigationMeasureCategory\Update\UpdateMitigationMeasureCategoryService;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRegistered;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRemoved;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryTranslated;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryUpdated;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMitigationMeasureCategoryRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMitigationMeasureRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class MitigationMeasureCategoryServicesTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;
    private InMemoryMitigationMeasureCategoryRepository $repository;

    protected function setUp(): void
    {
        FakeLocaleSetter::reset();
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
        $this->repository = MitigationMeasureCategoryRepositoryMother::inMemory();
    }

    public function testRegister(): void
    {
        $sut = new RegisterMitigationMeasureCategoryService($this->repository, new MitigationMeasureCategoryAuthorizer());

        $response = $sut(new RegisterMitigationMeasureCategoryRequest(name: 'Energía'), UserMother::adminUser());

        $this->assertNotNull($this->repository->find($response->mitigationMeasureCategoryId));
        $this->assertSame('Energía', $response->name);
        $this->assertInstanceOf(MitigationMeasureCategoryRegistered::class, $this->spySubscriber->lastEvent());
        $this->assertTrue($sut->isHandled(new RegisterMitigationMeasureCategoryRequest(name: 'x')));
    }

    public function testRegisterInAnotherLocaleTranslates(): void
    {
        FakeLocaleSetter::set(Locale::English);
        $sut = new RegisterMitigationMeasureCategoryService($this->repository, new MitigationMeasureCategoryAuthorizer());

        $sut(new RegisterMitigationMeasureCategoryRequest(name: 'Energy'), UserMother::adminUser());

        $this->assertInstanceOf(MitigationMeasureCategoryTranslated::class, $this->spySubscriber->lastEvent());
    }

    public function testRegisterDeniedForOwner(): void
    {
        $sut = new RegisterMitigationMeasureCategoryService($this->repository, new MitigationMeasureCategoryAuthorizer());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new RegisterMitigationMeasureCategoryRequest(name: 'Energía'), UserMother::fromUsernameAndRole('owner', Role::Owner));
    }

    public function testUpdate(): void
    {
        $category = MitigationMeasureCategoryMother::fromName('Energía');
        $this->repository->add($category);
        $sut = new UpdateMitigationMeasureCategoryService($this->repository, new MitigationMeasureCategoryAuthorizer());

        $sut(new UpdateMitigationMeasureCategoryRequest($category->id(), 'Energía renovable'), UserMother::adminUser());

        $this->assertSame('Energía renovable', $this->repository->find($category->id())?->name());
        $this->assertInstanceOf(MitigationMeasureCategoryUpdated::class, $this->spySubscriber->lastEvent());
    }

    public function testRemove(): void
    {
        $category = MitigationMeasureCategoryMother::fromName('Energía');
        $this->repository->add($category);

        $this->removeService(new InMemoryMitigationMeasureRepository())(new RemoveMitigationMeasureCategoryRequest($category->id()), UserMother::adminUser());

        $this->assertNull($this->repository->find($category->id()));
        $this->assertInstanceOf(MitigationMeasureCategoryRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testRemoveRejectsACategoryInUse(): void
    {
        $category = MitigationMeasureCategoryMother::fromName('Energía');
        $this->repository->add($category);
        $measures = new InMemoryMitigationMeasureRepository();
        $measures->add(MitigationMeasureMother::from(name: 'Paneles', id: (string) MitigationMeasureId::generate(), categoryId: $category->id()));

        $this->expectException(\InvalidArgumentException::class);

        $this->removeService($measures)(new RemoveMitigationMeasureCategoryRequest($category->id()), UserMother::adminUser());
    }

    public function testList(): void
    {
        $this->repository->add(MitigationMeasureCategoryMother::fromName('Energía'));
        $this->repository->add(MitigationMeasureCategoryMother::fromName('Movilidad'));
        $sut = new ListMitigationMeasureCategoriesService($this->repository, new MitigationMeasureCategoryAuthorizer());

        $response = $sut(new ListMitigationMeasureCategoriesRequest(), UserMother::adminUser());

        $this->assertCount(2, $response->mitigationMeasureCategories);
        $this->assertSame(2, $response->totalResults);
    }

    private function removeService(InMemoryMitigationMeasureRepository $measures): RemoveMitigationMeasureCategoryService
    {
        return new RemoveMitigationMeasureCategoryService($this->repository, $measures, new MitigationMeasureCategoryAuthorizer());
    }
}
