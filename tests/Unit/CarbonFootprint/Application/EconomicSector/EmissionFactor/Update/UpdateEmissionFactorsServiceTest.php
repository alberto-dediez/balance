<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EconomicSector\EmissionFactor\Update;

use App\CarbonFootprint\Application\EconomicSector\EconomicSectorAuthorizer;
use App\CarbonFootprint\Application\EconomicSector\EmissionFactor\EmissionFactorRow;
use App\CarbonFootprint\Application\EconomicSector\EmissionFactor\Update\UpdateEmissionFactorsRequest;
use App\CarbonFootprint\Application\EconomicSector\EmissionFactor\Update\UpdateEmissionFactorsService;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class UpdateEmissionFactorsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testAddsEmissionFactorForYear(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory();

        $economicSector = EconomicSectorMother::random();
        $repository->add($economicSector);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, $emissionFactorGroupRepository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new EmissionFactorRow(
                    economicSectorId: (string) $economicSector->id(),
                    economicSectorName: $economicSector->name(),
                    co2eq: '1.5',
                    co2: '1.0',
                    ch4: '0.2',
                    n2o: '0.3',
                    source: 'IPCC',
                    emissionFactorGroupId: (string) $emissionFactorGroup->id(),
                ),
            ],
        );

        $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $persisted = $repository->find($economicSector->id());

        $this->assertNotNull($persisted);
        $factor = $persisted->emissionFactorFor(2025, $emissionFactorGroup->id());
        $this->assertNotNull($factor);
        $this->assertSame('1.5', $factor->emissionFactor()->co2eq());
    }

    public function testRemovesEmissionFactorWhenRowIsEmpty(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory();

        $emissionFactorGroup = EmissionFactorGroupMother::default();

        $economicSector = EconomicSectorMother::random();
        $ef = new EmissionFactor('1', '1', '1', '1', 2025, emissionFactorGroupId: $emissionFactorGroup->id(), unit: $economicSector->unit());
        $economicSector->setEmissionFactor($ef);
        $repository->add($economicSector);

        $sut = $this->createSUT($repository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new EmissionFactorRow(
                    economicSectorId: (string) $economicSector->id(),
                    economicSectorName: $economicSector->name(),
                    emissionFactorGroupId: (string) $emissionFactorGroup->id(),
                ),
            ],
        );

        $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $persisted = $repository->find($economicSector->id());

        $this->assertNotNull($persisted);
        $this->assertNull($persisted->emissionFactorFor(2025, $emissionFactorGroup->id()));
    }

    public function testUpdatesExistingEmissionFactor(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory();

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $economicSector = EconomicSectorMother::random();
        $economicSector->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2025, emissionFactorGroupId: $emissionFactorGroup->id(), unit: $economicSector->unit()));
        $repository->add($economicSector);

        $sut = $this->createSUT($repository, $emissionFactorGroupRepository);

        $sut(
            new UpdateEmissionFactorsRequest(
                year: 2025,
                rows: [
                    new EmissionFactorRow(
                        economicSectorId: (string) $economicSector->id(),
                        economicSectorName: $economicSector->name(),
                        co2eq: '2.5',
                        co2: '2.0',
                        ch4: '0.4',
                        n2o: '0.6',
                        emissionFactorGroupId: (string) $emissionFactorGroup->id(),
                    ),
                ],
            ),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );

        $persisted = $repository->find($economicSector->id());

        $this->assertNotNull($persisted);
        $this->assertCount(1, $persisted->emissionFactors());
        $factor = $persisted->emissionFactorFor(2025, $emissionFactorGroup->id());
        $this->assertNotNull($factor);
        $this->assertSame('2.5', $factor->emissionFactor()->co2eq());
        $this->assertSame(Unit::Euro, $factor->emissionFactor()->unit());
    }

    public function testOwnerCannotUpdate(): void
    {
        $sut = $this->createSUT(EconomicSectorRepositoryMother::inMemory());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new UpdateEmissionFactorsRequest(year: 2025), UserMother::fromUsernameAndRole('owner', Role::Owner));
    }

    private function createSUT(
        EconomicSectorRepository $repository,
        ?EmissionFactorGroupRepository $emissionFactorGroupRepository = null,
    ): UpdateEmissionFactorsService {
        return new UpdateEmissionFactorsService(
            $repository,
            new EconomicSectorAuthorizer(),
            $emissionFactorGroupRepository ?? EmissionFactorGroupRepositoryMother::inMemory(),
        );
    }
}
