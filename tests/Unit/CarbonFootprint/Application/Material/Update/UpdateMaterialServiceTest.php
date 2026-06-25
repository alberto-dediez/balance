<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Material\Update;

use App\CarbonFootprint\Application\Material\MaterialAuthorizer;
use App\CarbonFootprint\Application\Material\Update\EmissionFactorData;
use App\CarbonFootprint\Application\Material\Update\UpdateMaterialRequest;
use App\CarbonFootprint\Application\Material\Update\UpdateMaterialService;
use App\CarbonFootprint\Domain\Model\Material\MaterialCategory;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\CarbonFootprint\Domain\Model\Material\MaterialTranslated;
use App\CarbonFootprint\Domain\Model\Material\MaterialUpdated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateMaterialServiceTest extends TestCase
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
        $repository = MaterialRepositoryMother::inMemory();

        $material = MaterialMother::random();

        $repository->add($material);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateMaterialRequest(
            materialId: $material->id(),
            name: 'changedName',
            category: MaterialCategory::Glass,
            unit: Unit::Piece,
            unitWeight: '0.5',
        );

        $result = $sut($request, $user);

        $materialPersisted = $repository->find($result->materialId);

        $this->assertNotNull($materialPersisted);
        $this->assertSame('0.5', $materialPersisted->unitWeight());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MaterialUpdated);
        $this->assertTrue($materialPersisted->id()->equals($lastEvent->materialId()));
    }

    public function testUpdateWithLocaleTranslate(): void
    {
        $repository = MaterialRepositoryMother::inMemory(Locale::Spanish);

        $material = MaterialMother::random();

        $repository->add($material);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new UpdateMaterialRequest(
            materialId: $material->id(),
            name: 'changedName',
            category: MaterialCategory::Metal,
            unit: Unit::Kilogram,
        );

        $result = $sut($request, $user);

        $materialPersisted = $repository->find($result->materialId);

        $this->assertNotNull($materialPersisted);
        $this->assertEquals('changedName', $materialPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof MaterialTranslated);
        $this->assertTrue($materialPersisted->id()->equals($lastEvent->materialId()));
    }

    public function testUpdateSyncsEmissionFactors(): void
    {
        $repository = MaterialRepositoryMother::inMemory();

        $material = MaterialMother::random();

        $repository->add($material);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateMaterialRequest(
            materialId: $material->id(),
            name: 'changedName',
            category: MaterialCategory::Glass,
            unit: Unit::Piece,
            emissionFactors: [
                new EmissionFactorData(
                    year: 2024,
                    co2eq: '1.5',
                    co2: '1.0',
                    ch4: '0.2',
                    n2o: '0.3',
                    source: 'IPCC',
                ),
            ],
        );

        $result = $sut($request, $user);

        $materialPersisted = $repository->find($result->materialId);

        $this->assertNotNull($materialPersisted);
        $this->assertCount(1, $materialPersisted->emissionFactors());

        $persistedFactor = $materialPersisted->emissionFactorFor(2024);

        $this->assertNotNull($persistedFactor);
        $this->assertSame('1.5', $persistedFactor->co2eq());
        // The emission factor unit is derived from the material's unit, not user input.
        $this->assertSame(Unit::Piece, $persistedFactor->unit());
    }

    private function createSUT(MaterialRepository $repository, Locale $locale = Locale::Spanish): UpdateMaterialService
    {
        FakeLocaleSetter::set($locale);

        return new UpdateMaterialService(
            materialRepository: $repository,
            authorizer: new MaterialAuthorizer(),
        );
    }
}
