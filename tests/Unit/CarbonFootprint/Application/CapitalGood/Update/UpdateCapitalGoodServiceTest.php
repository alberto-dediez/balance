<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CapitalGood\Update;

use App\CarbonFootprint\Application\CapitalGood\CapitalGoodAuthorizer;
use App\CarbonFootprint\Application\CapitalGood\Update\EmissionFactorData;
use App\CarbonFootprint\Application\CapitalGood\Update\UpdateCapitalGoodRequest;
use App\CarbonFootprint\Application\CapitalGood\Update\UpdateCapitalGoodService;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodCategory;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodTranslated;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodUpdated;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateCapitalGoodServiceTest extends TestCase
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
        $repository = CapitalGoodRepositoryMother::inMemory();

        $capitalGood = CapitalGoodMother::random();

        $repository->add($capitalGood);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateCapitalGoodRequest(
            capitalGoodId: $capitalGood->id(),
            name: 'changedName',
            category: CapitalGoodCategory::VehiclesAndMachinery,
            unit: Unit::Piece,
        );

        $result = $sut($request, $user);

        $capitalGoodPersisted = $repository->find($result->capitalGoodId);

        $this->assertNotNull($capitalGoodPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CapitalGoodUpdated);
        $this->assertTrue($capitalGoodPersisted->id()->equals($lastEvent->capitalGoodId()));
    }

    public function testUpdateWithLocaleTranslate(): void
    {
        $repository = CapitalGoodRepositoryMother::inMemory(Locale::Spanish);

        $capitalGood = CapitalGoodMother::random();

        $repository->add($capitalGood);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new UpdateCapitalGoodRequest(
            capitalGoodId: $capitalGood->id(),
            name: 'changedName',
            category: CapitalGoodCategory::Renovations,
            unit: Unit::Euro,
        );

        $result = $sut($request, $user);

        $capitalGoodPersisted = $repository->find($result->capitalGoodId);

        $this->assertNotNull($capitalGoodPersisted);
        $this->assertEquals('changedName', $capitalGoodPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CapitalGoodTranslated);
        $this->assertTrue($capitalGoodPersisted->id()->equals($lastEvent->capitalGoodId()));
    }

    public function testUpdateSyncsEmissionFactors(): void
    {
        $repository = CapitalGoodRepositoryMother::inMemory();

        $capitalGood = CapitalGoodMother::random();

        $repository->add($capitalGood);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, Locale::Spanish, $emissionFactorGroupRepository);

        $request = new UpdateCapitalGoodRequest(
            capitalGoodId: $capitalGood->id(),
            name: 'changedName',
            category: CapitalGoodCategory::VehiclesAndMachinery,
            unit: Unit::Piece,
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

        $capitalGoodPersisted = $repository->find($result->capitalGoodId);

        $this->assertNotNull($capitalGoodPersisted);
        $this->assertCount(1, $capitalGoodPersisted->emissionFactors());

        $persistedFactor = $capitalGoodPersisted->emissionFactorFor(2024, $emissionFactorGroup->id());

        $this->assertNotNull($persistedFactor);
        $this->assertSame('1.5', $persistedFactor->emissionFactor()->co2eq());
        $this->assertSame(Unit::Piece, $persistedFactor->emissionFactor()->unit());
    }

    public function testUnitChangeRestampsEmissionFactors(): void
    {
        $repository = CapitalGoodRepositoryMother::inMemory();

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $capitalGood = CapitalGoodMother::random();
        $capitalGood->setEmissionFactor(new EmissionFactor('1.5', '1.0', '0.2', '0.3', 2024, emissionFactorGroupId: $emissionFactorGroup->id(), unit: Unit::Euro));
        $repository->add($capitalGood);

        $sut = $this->createSUT($repository, Locale::Spanish, $emissionFactorGroupRepository);

        $sut(
            new UpdateCapitalGoodRequest(
                capitalGoodId: $capitalGood->id(),
                name: $capitalGood->name(),
                category: CapitalGoodCategory::VehiclesAndMachinery,
                unit: Unit::UsDollar,
                emissionFactors: [
                    new EmissionFactorData(
                        year: 2024,
                        co2eq: '1.5',
                        co2: '1.0',
                        ch4: '0.2',
                        n2o: '0.3',
                        emissionFactorGroupId: (string) $emissionFactorGroup->id(),
                    ),
                ],
            ),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );

        $persisted = $repository->find($capitalGood->id());

        $this->assertNotNull($persisted);
        $this->assertSame(Unit::UsDollar, $persisted->unit());
        $this->assertSame(Unit::UsDollar, $persisted->emissionFactorFor(2024, $emissionFactorGroup->id())?->emissionFactor()->unit());
    }

    public function testThrowsWhenEmissionFactorGroupNotFound(): void
    {
        $repository = CapitalGoodRepositoryMother::inMemory();
        $capitalGood = CapitalGoodMother::random();
        $repository->add($capitalGood);

        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new UpdateCapitalGoodRequest(
                capitalGoodId: $capitalGood->id(),
                name: 'changedName',
                category: CapitalGoodCategory::VehiclesAndMachinery,
                unit: Unit::Euro,
                emissionFactors: [
                    new EmissionFactorData(year: 2024, co2eq: '1', co2: '1', ch4: '1', n2o: '1', emissionFactorGroupId: (string) CapitalGoodId::generate()),
                ],
            ),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );
    }

    public function testOwnerCannotUpdate(): void
    {
        $repository = CapitalGoodRepositoryMother::inMemory();
        $capitalGood = CapitalGoodMother::random();
        $repository->add($capitalGood);

        $sut = $this->createSUT($repository);

        $this->expectException(UnauthorizedActionException::class);

        $sut(
            new UpdateCapitalGoodRequest(
                capitalGoodId: $capitalGood->id(),
                name: 'changedName',
                category: CapitalGoodCategory::VehiclesAndMachinery,
                unit: Unit::Euro,
            ),
            UserMother::fromUsernameAndRole('owner', Role::Owner),
        );
    }

    private function createSUT(
        CapitalGoodRepository $repository,
        Locale $locale = Locale::Spanish,
        ?EmissionFactorGroupRepository $emissionFactorGroupRepository = null,
    ): UpdateCapitalGoodService {
        $emissionFactorGroupRepository ??= EmissionFactorGroupRepositoryMother::inMemory();

        FakeLocaleSetter::set($locale);

        return new UpdateCapitalGoodService(
            capitalGoodRepository: $repository,
            authorizer: new CapitalGoodAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository,
        );
    }
}
