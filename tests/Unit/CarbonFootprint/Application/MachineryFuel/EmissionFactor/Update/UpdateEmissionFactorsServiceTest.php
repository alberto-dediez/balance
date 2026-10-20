<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MachineryFuel\EmissionFactor\Update;

use App\CarbonFootprint\Application\MachineryFuel\EmissionFactor\EmissionFactorRow;
use App\CarbonFootprint\Application\MachineryFuel\EmissionFactor\Update\UpdateEmissionFactorsRequest;
use App\CarbonFootprint\Application\MachineryFuel\EmissionFactor\Update\UpdateEmissionFactorsService;
use App\CarbonFootprint\Application\MachineryFuel\MachineryFuelAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepositoryMother;
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
        $repository = MachineryFuelRepositoryMother::inMemory();

        $machineryFuel = MachineryFuelMother::random();
        $repository->add($machineryFuel);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, $emissionFactorGroupRepository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new EmissionFactorRow(
                    machineryFuelId: (string) $machineryFuel->id(),
                    machineryFuelName: $machineryFuel->name(),
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

        $persisted = $repository->find($machineryFuel->id());

        $this->assertNotNull($persisted);
        $factor = $persisted->emissionFactorFor(2025, $emissionFactorGroup->id());
        $this->assertNotNull($factor);
        $this->assertSame('1.5', $factor->co2eq());
    }

    public function testAddsWellToTankFactorForYear(): void
    {
        $repository = MachineryFuelRepositoryMother::inMemory();

        $machineryFuel = MachineryFuelMother::random();
        $repository->add($machineryFuel);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, $emissionFactorGroupRepository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new EmissionFactorRow(
                    machineryFuelId: (string) $machineryFuel->id(),
                    machineryFuelName: $machineryFuel->name(),
                    co2eq: '1.5',
                    co2: '1.0',
                    ch4: '0.2',
                    n2o: '0.3',
                    wellToTankCo2eq: '0.61',
                    wellToTankCo2: '0.6',
                    wellToTankCh4: '0.01',
                    wellToTankN2o: '0.01',
                    wellToTankSource: 'DEFRA',
                    source: 'IPCC',
                    emissionFactorGroupId: (string) $emissionFactorGroup->id(),
                ),
            ],
        );

        $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $persisted = $repository->find($machineryFuel->id());

        $this->assertNotNull($persisted);
        $factor = $persisted->wellToTankFactorFor(2025, $emissionFactorGroup->id());
        $this->assertNotNull($factor);
        $this->assertSame('0.61', $factor->co2eq());
        $this->assertSame('DEFRA', $factor->source());
    }

    public function testStoresZeroWellToTankWhenRowHasNoWellToTank(): void
    {
        $repository = MachineryFuelRepositoryMother::inMemory();

        $machineryFuel = MachineryFuelMother::random();
        $repository->add($machineryFuel);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, $emissionFactorGroupRepository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new EmissionFactorRow(
                    machineryFuelId: (string) $machineryFuel->id(),
                    machineryFuelName: $machineryFuel->name(),
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

        $persisted = $repository->find($machineryFuel->id());

        $this->assertNotNull($persisted);
        $factor = $persisted->wellToTankFactorFor(2025, $emissionFactorGroup->id());
        $this->assertNotNull($factor);
        $this->assertSame('0', $factor->co2eq());
        $this->assertNull($factor->source());
    }

    public function testRemovesEmissionFactorWhenRowIsEmpty(): void
    {
        $repository = MachineryFuelRepositoryMother::inMemory();

        $emissionFactorGroup = EmissionFactorGroupMother::default();

        $machineryFuel = MachineryFuelMother::random();
        $machineryFuel->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2025, emissionFactorGroupId: $emissionFactorGroup->id()));
        $repository->add($machineryFuel);

        $sut = $this->createSUT($repository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new EmissionFactorRow(
                    machineryFuelId: (string) $machineryFuel->id(),
                    machineryFuelName: $machineryFuel->name(),
                    emissionFactorGroupId: (string) $emissionFactorGroup->id(),
                ),
            ],
        );

        $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $persisted = $repository->find($machineryFuel->id());

        $this->assertNotNull($persisted);
        $this->assertNull($persisted->emissionFactorFor(2025, $emissionFactorGroup->id()));
    }

    private function createSUT(
        MachineryFuelRepository $repository,
        ?EmissionFactorGroupRepository $emissionFactorGroupRepository = null,
    ): UpdateEmissionFactorsService {
        return new UpdateEmissionFactorsService(
            $repository,
            new MachineryFuelAuthorizer(),
            $emissionFactorGroupRepository ?? EmissionFactorGroupRepositoryMother::inMemory(),
        );
    }
}
