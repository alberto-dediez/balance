<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\GdOFactor\EmissionFactor\Update;

use App\CarbonFootprint\Application\GdOFactor\EmissionFactor\EmissionFactorRow;
use App\CarbonFootprint\Application\GdOFactor\EmissionFactor\Update\UpdateEmissionFactorsRequest;
use App\CarbonFootprint\Application\GdOFactor\EmissionFactor\Update\UpdateEmissionFactorsService;
use App\CarbonFootprint\Application\GdOFactor\GdOFactorAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
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
        $repository = GdOFactorRepositoryMother::inMemory();

        $gdOFactor = GdOFactorMother::random();
        $repository->add($gdOFactor);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, $emissionFactorGroupRepository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new EmissionFactorRow(
                    gdOFactorId: (string) $gdOFactor->id(),
                    gdOFactorName: $gdOFactor->name(),
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

        $persisted = $repository->find($gdOFactor->id());

        $this->assertNotNull($persisted);
        $factor = $persisted->emissionFactorFor(2025, $emissionFactorGroup->id());
        $this->assertNotNull($factor);
        $this->assertSame('1.5', $factor->co2eq());
    }

    public function testAddsWellToTankFactorForYear(): void
    {
        $repository = GdOFactorRepositoryMother::inMemory();

        $gdOFactor = GdOFactorMother::random();
        $repository->add($gdOFactor);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, $emissionFactorGroupRepository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new EmissionFactorRow(
                    gdOFactorId: (string) $gdOFactor->id(),
                    gdOFactorName: $gdOFactor->name(),
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

        $persisted = $repository->find($gdOFactor->id());

        $this->assertNotNull($persisted);
        $factor = $persisted->wellToTankFactorFor(2025, $emissionFactorGroup->id());
        $this->assertNotNull($factor);
        $this->assertSame('0.61', $factor->co2eq());
        $this->assertSame('DEFRA', $factor->source());
    }

    public function testStoresZeroWellToTankWhenRowHasNoWellToTank(): void
    {
        $repository = GdOFactorRepositoryMother::inMemory();

        $gdOFactor = GdOFactorMother::random();
        $repository->add($gdOFactor);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, $emissionFactorGroupRepository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new EmissionFactorRow(
                    gdOFactorId: (string) $gdOFactor->id(),
                    gdOFactorName: $gdOFactor->name(),
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

        $persisted = $repository->find($gdOFactor->id());

        $this->assertNotNull($persisted);
        $factor = $persisted->wellToTankFactorFor(2025, $emissionFactorGroup->id());
        $this->assertNotNull($factor);
        $this->assertSame('0', $factor->co2eq());
        $this->assertNull($factor->source());
    }

    public function testRemovesEmissionFactorWhenRowIsEmpty(): void
    {
        $repository = GdOFactorRepositoryMother::inMemory();

        $emissionFactorGroup = EmissionFactorGroupMother::default();

        $gdOFactor = GdOFactorMother::random();
        $gdOFactor->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2025, emissionFactorGroupId: $emissionFactorGroup->id()));
        $repository->add($gdOFactor);

        $sut = $this->createSUT($repository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new EmissionFactorRow(
                    gdOFactorId: (string) $gdOFactor->id(),
                    gdOFactorName: $gdOFactor->name(),
                    emissionFactorGroupId: (string) $emissionFactorGroup->id(),
                ),
            ],
        );

        $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $persisted = $repository->find($gdOFactor->id());

        $this->assertNotNull($persisted);
        $this->assertNull($persisted->emissionFactorFor(2025, $emissionFactorGroup->id()));
    }

    public function testIsHandled(): void
    {
        $repository = GdOFactorRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->assertTrue($sut->isHandled(new UpdateEmissionFactorsRequest(year: 2025)));
    }

    private function createSUT(
        GdOFactorRepository $repository,
        ?EmissionFactorGroupRepository $emissionFactorGroupRepository = null,
    ): UpdateEmissionFactorsService {
        return new UpdateEmissionFactorsService(
            $repository,
            new GdOFactorAuthorizer(),
            $emissionFactorGroupRepository ?? EmissionFactorGroupRepositoryMother::inMemory(),
        );
    }
}
