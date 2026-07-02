<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Waste\Update;

use App\CarbonFootprint\Application\Waste\Update\EmissionFactorData;
use App\CarbonFootprint\Application\Waste\Update\UpdateWasteRequest;
use App\CarbonFootprint\Application\Waste\Update\UpdateWasteService;
use App\CarbonFootprint\Application\Waste\WasteAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;
use App\CarbonFootprint\Domain\Model\Waste\WasteTranslated;
use App\CarbonFootprint\Domain\Model\Waste\WasteUpdated;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Waste\WasteRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateWasteServiceTest extends TestCase
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
        $repository = WasteRepositoryMother::inMemory();

        $waste = WasteMother::random();

        $repository->add($waste);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateWasteRequest(
            wasteId: $waste->id(),
            name: 'changedName',
            unit: Unit::Piece,
            density: 1.7,
        );

        $result = $sut($request, $user);

        $wastePersisted = $repository->find($result->wasteId);

        $this->assertNotNull($wastePersisted);
        $this->assertSame(Unit::Piece, $wastePersisted->unit());
        $this->assertSame(1.7, $wastePersisted->density());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof WasteUpdated);
        $this->assertTrue($wastePersisted->id()->equals($lastEvent->wasteId()));
    }

    public function testUpdateWithLocaleTranslate(): void
    {
        $repository = WasteRepositoryMother::inMemory(Locale::Spanish);

        $waste = WasteMother::random();

        $repository->add($waste);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new UpdateWasteRequest(
            wasteId: $waste->id(),
            name: 'changedName',
            unit: Unit::Kilogram,
            density: 1.0,
        );

        $result = $sut($request, $user);

        $wastePersisted = $repository->find($result->wasteId);

        $this->assertNotNull($wastePersisted);
        $this->assertEquals('changedName', $wastePersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof WasteTranslated);
        $this->assertTrue($wastePersisted->id()->equals($lastEvent->wasteId()));
    }

    public function testUpdateSyncsEmissionFactors(): void
    {
        $repository = WasteRepositoryMother::inMemory();

        $waste = WasteMother::random();

        $repository->add($waste);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, Locale::Spanish, $emissionFactorGroupRepository);

        $request = new UpdateWasteRequest(
            wasteId: $waste->id(),
            name: 'changedName',
            unit: Unit::Piece,
            density: 1.0,
            emissionFactors: [
                new EmissionFactorData(
                    year: 2024,
                    co2eq: '1.5',
                    co2: '1.0',
                    ch4: '0.2',
                    n2o: '0.3',
                    source: 'IPCC',
                    emissionFactorGroupId: (string) $emissionFactorGroup->id(),
                ),
            ],
        );

        $result = $sut($request, $user);

        $wastePersisted = $repository->find($result->wasteId);

        $this->assertNotNull($wastePersisted);
        $this->assertCount(1, $wastePersisted->emissionFactors());

        $persistedFactor = $wastePersisted->emissionFactorFor(2024, $emissionFactorGroup->id());

        $this->assertNotNull($persistedFactor);
        // co2eq is respected as provided by the user (non-zero), not recomputed from co2/ch4/n2o.
        $this->assertSame('1.5', $persistedFactor->co2eq());
        $this->assertSame(Unit::Piece, $persistedFactor->unit());
    }

    private function createSUT(
        WasteRepository $repository,
        Locale $locale = Locale::Spanish,
        ?EmissionFactorGroupRepository $emissionFactorGroupRepository = null,
    ): UpdateWasteService {
        $emissionFactorGroupRepository ??= EmissionFactorGroupRepositoryMother::inMemory();

        FakeLocaleSetter::set($locale);

        return new UpdateWasteService(
            wasteRepository: $repository,
            authorizer: new WasteAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository,
        );
    }
}
