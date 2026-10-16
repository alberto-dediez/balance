<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EconomicSector\Update;

use App\CarbonFootprint\Application\EconomicSector\EconomicSectorAuthorizer;
use App\CarbonFootprint\Application\EconomicSector\Update\EmissionFactorData;
use App\CarbonFootprint\Application\EconomicSector\Update\UpdateEconomicSectorRequest;
use App\CarbonFootprint\Application\EconomicSector\Update\UpdateEconomicSectorService;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorCodeAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorTranslated;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorUpdated;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateEconomicSectorServiceTest extends TestCase
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
        $repository = EconomicSectorRepositoryMother::inMemory();

        $economicSector = EconomicSectorMother::random();

        $repository->add($economicSector);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateEconomicSectorRequest(
            economicSectorId: $economicSector->id(),
            name: 'changedName',
            unit: Unit::Euro,
        );

        $result = $sut($request, $user);

        $economicSectorPersisted = $repository->find($result->economicSectorId);

        $this->assertNotNull($economicSectorPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EconomicSectorUpdated);
        $this->assertTrue($economicSectorPersisted->id()->equals($lastEvent->economicSectorId()));
    }

    public function testUpdateWithClassificationCodeOfAnotherSectorThrows(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory();
        $repository->add(EconomicSectorMother::withCode('25'));

        $economicSector = EconomicSectorMother::withCode('26');
        $repository->add($economicSector);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $this->expectException(EconomicSectorCodeAlreadyExistsException::class);

        $sut(new UpdateEconomicSectorRequest(
            economicSectorId: $economicSector->id(),
            name: 'changedName',
            unit: Unit::Euro,
            classificationCode: '25',
        ), UserMother::adminUser());
    }

    public function testUpdateKeepingOwnClassificationCode(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory();

        $economicSector = EconomicSectorMother::withCode('25');
        $repository->add($economicSector);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $sut(new UpdateEconomicSectorRequest(
            economicSectorId: $economicSector->id(),
            name: 'changedName',
            unit: Unit::Euro,
            classificationCode: '25',
        ), UserMother::adminUser());

        $persisted = $repository->find($economicSector->id());

        $this->assertNotNull($persisted);
        $this->assertSame('25', $persisted->classificationCode());
        $this->assertSame('changedName', $persisted->name());
    }

    public function testUpdateWithLocaleTranslate(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory(Locale::Spanish);

        $economicSector = EconomicSectorMother::random();

        $repository->add($economicSector);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new UpdateEconomicSectorRequest(
            economicSectorId: $economicSector->id(),
            name: 'changedName',
            unit: Unit::Euro,
        );

        $result = $sut($request, $user);

        $economicSectorPersisted = $repository->find($result->economicSectorId);

        $this->assertNotNull($economicSectorPersisted);
        $this->assertEquals('changedName', $economicSectorPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EconomicSectorTranslated);
        $this->assertTrue($economicSectorPersisted->id()->equals($lastEvent->economicSectorId()));
    }

    public function testUpdateSyncsEmissionFactors(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory();

        $economicSector = EconomicSectorMother::random();

        $repository->add($economicSector);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, Locale::Spanish, $emissionFactorGroupRepository);

        $request = new UpdateEconomicSectorRequest(
            economicSectorId: $economicSector->id(),
            name: 'changedName',
            unit: Unit::Euro,
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

        $economicSectorPersisted = $repository->find($result->economicSectorId);

        $this->assertNotNull($economicSectorPersisted);
        $this->assertCount(1, $economicSectorPersisted->emissionFactors());

        $persistedFactor = $economicSectorPersisted->emissionFactorFor(2024, $emissionFactorGroup->id());

        $this->assertNotNull($persistedFactor);
        $this->assertSame('1.5', $persistedFactor->emissionFactor()->co2eq());
        $this->assertSame(Unit::Euro, $persistedFactor->emissionFactor()->unit());
    }

    public function testUnitChangeRestampsEmissionFactors(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory();

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $economicSector = EconomicSectorMother::random();
        $economicSector->setEmissionFactor(new EmissionFactor('1.5', '1.0', '0.2', '0.3', 2024, emissionFactorGroupId: $emissionFactorGroup->id(), unit: Unit::Euro));
        $repository->add($economicSector);

        $sut = $this->createSUT($repository, Locale::Spanish, $emissionFactorGroupRepository);

        $sut(
            new UpdateEconomicSectorRequest(
                economicSectorId: $economicSector->id(),
                name: $economicSector->name(),
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

        $persisted = $repository->find($economicSector->id());

        $this->assertNotNull($persisted);
        $this->assertSame(Unit::UsDollar, $persisted->unit());
        $this->assertSame(Unit::UsDollar, $persisted->emissionFactorFor(2024, $emissionFactorGroup->id())?->emissionFactor()->unit());
    }

    public function testThrowsWhenEmissionFactorGroupNotFound(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory();
        $economicSector = EconomicSectorMother::random();
        $repository->add($economicSector);

        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new UpdateEconomicSectorRequest(
                economicSectorId: $economicSector->id(),
                name: 'changedName',
                unit: Unit::Euro,
                emissionFactors: [
                    new EmissionFactorData(year: 2024, co2eq: '1', co2: '1', ch4: '1', n2o: '1', emissionFactorGroupId: (string) EconomicSectorId::generate()),
                ],
            ),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );
    }

    public function testOwnerCannotUpdate(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory();
        $economicSector = EconomicSectorMother::random();
        $repository->add($economicSector);

        $sut = $this->createSUT($repository);

        $this->expectException(UnauthorizedActionException::class);

        $sut(
            new UpdateEconomicSectorRequest(
                economicSectorId: $economicSector->id(),
                name: 'changedName',
                unit: Unit::Euro,
            ),
            UserMother::fromUsernameAndRole('owner', Role::Owner),
        );
    }

    private function createSUT(
        EconomicSectorRepository $repository,
        Locale $locale = Locale::Spanish,
        ?EmissionFactorGroupRepository $emissionFactorGroupRepository = null,
    ): UpdateEconomicSectorService {
        $emissionFactorGroupRepository ??= EmissionFactorGroupRepositoryMother::inMemory();

        FakeLocaleSetter::set($locale);

        return new UpdateEconomicSectorService(
            economicSectorRepository: $repository,
            authorizer: new EconomicSectorAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository,
        );
    }
}
