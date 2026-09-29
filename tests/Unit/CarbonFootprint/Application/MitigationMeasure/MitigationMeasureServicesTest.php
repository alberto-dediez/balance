<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MitigationMeasure;

use App\CarbonFootprint\Application\MitigationMeasure\List\ListMitigationMeasuresRequest;
use App\CarbonFootprint\Application\MitigationMeasure\List\ListMitigationMeasuresService;
use App\CarbonFootprint\Application\MitigationMeasure\MitigationMeasureAuthorizer;
use App\CarbonFootprint\Application\MitigationMeasure\Register\RegisterMitigationMeasureRequest;
use App\CarbonFootprint\Application\MitigationMeasure\Register\RegisterMitigationMeasureService;
use App\CarbonFootprint\Application\MitigationMeasure\Remove\RemoveMitigationMeasureRequest;
use App\CarbonFootprint\Application\MitigationMeasure\Remove\RemoveMitigationMeasureService;
use App\CarbonFootprint\Application\MitigationMeasure\Update\MitigationMeasureEffectData;
use App\CarbonFootprint\Application\MitigationMeasure\Update\UpdateMitigationMeasureRequest;
use App\CarbonFootprint\Application\MitigationMeasure\Update\UpdateMitigationMeasureService;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureRemoved;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategory;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMitigationMeasureCategoryRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMitigationMeasureRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class MitigationMeasureServicesTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;
    private InMemoryMitigationMeasureRepository $repository;
    private InMemoryMitigationMeasureCategoryRepository $categories;
    private MitigationMeasureCategory $category;

    protected function setUp(): void
    {
        FakeLocaleSetter::reset();
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
        $this->repository = MitigationMeasureRepositoryMother::inMemory();
        $this->categories = new InMemoryMitigationMeasureCategoryRepository();
        $this->category = MitigationMeasureCategoryMother::fromName('Energía');
        $this->categories->add($this->category);
    }

    public function testRegisterWithEffects(): void
    {
        $sut = new RegisterMitigationMeasureService($this->repository, $this->categories, new MitigationMeasureAuthorizer());

        $response = $sut(new RegisterMitigationMeasureRequest(
            name: 'Paneles solares',
            description: 'Instalar paneles',
            categoryId: $this->category->id(),
            effects: [new MitigationMeasureEffectData(Source::BuildingElectricity, 0.1, 0.4)],
        ), UserMother::adminUser());

        $measure = $this->repository->find($response->mitigationMeasureId);
        $this->assertNotNull($measure);
        $this->assertSame('Paneles solares', $measure->name());
        $this->assertSame(0.4, $measure->effects()->forSource(Source::BuildingElectricity)?->maximumReduction());
        $this->assertTrue($sut->isHandled(new RegisterMitigationMeasureRequest('x', 'y', $this->category->id())));
    }

    public function testRegisterRejectsAnUnknownCategory(): void
    {
        $sut = new RegisterMitigationMeasureService($this->repository, $this->categories, new MitigationMeasureAuthorizer());

        $this->expectException(\InvalidArgumentException::class);

        $sut(new RegisterMitigationMeasureRequest('Paneles', '', MitigationMeasureCategoryId::generate()), UserMother::adminUser());
    }

    public function testRegisterDeniedForOwner(): void
    {
        $sut = new RegisterMitigationMeasureService($this->repository, $this->categories, new MitigationMeasureAuthorizer());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new RegisterMitigationMeasureRequest('Paneles', '', $this->category->id()), UserMother::fromUsernameAndRole('owner', Role::Owner));
    }

    public function testUpdateInAnotherLocaleKeepsTheDefaultTextsAndSyncsEffects(): void
    {
        $measure = MitigationMeasureMother::withEffect(Source::FixedFacilities, 0.1, 0.3, 'Paneles solares', $this->category->id());
        $this->repository->add($measure);
        FakeLocaleSetter::set(Locale::English);
        $sut = new UpdateMitigationMeasureService($this->repository, $this->categories, new MitigationMeasureAuthorizer());

        $sut(new UpdateMitigationMeasureRequest(
            mitigationMeasureId: $measure->id(),
            name: 'Solar panels',
            description: 'Install them',
            categoryId: $this->category->id(),
            effects: [new MitigationMeasureEffectData(Source::BuildingElectricity, 0.2, 0.5)],
        ), UserMother::adminUser());

        $this->assertSame('Paneles solares', $measure->defaultName());
        $this->assertSame('Solar panels', $measure->name());
        $this->assertCount(1, $measure->effects());
        $this->assertTrue($measure->effects()->hasForSource(Source::BuildingElectricity));
    }

    public function testRemove(): void
    {
        $measure = MitigationMeasureMother::registeredMitigationMeasure();
        $this->repository->add($measure);
        $sut = new RemoveMitigationMeasureService($this->repository, new MitigationMeasureAuthorizer());

        $sut(new RemoveMitigationMeasureRequest($measure->id()), UserMother::adminUser());

        $this->assertNull($this->repository->find($measure->id()));
        $this->assertInstanceOf(MitigationMeasureRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testListFiltersByCategory(): void
    {
        $this->repository->add(MitigationMeasureMother::withEffect(Source::FixedFacilities, 0.1, 0.3, 'A', $this->category->id()));
        $this->repository->add(MitigationMeasureMother::random());
        $sut = new ListMitigationMeasuresService($this->repository, new MitigationMeasureAuthorizer());

        $response = $sut(new ListMitigationMeasuresRequest(categoryId: $this->category->id()), UserMother::adminUser());

        $this->assertCount(1, $response->mitigationMeasures);
        $this->assertSame(1, $response->totalResults);
    }
}
