<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Process\EmissionFactor\Update;

use App\CarbonFootprint\Application\Process\EmissionFactor\EmissionFactorRow;
use App\CarbonFootprint\Application\Process\EmissionFactor\Update\UpdateEmissionFactorsRequest;
use App\CarbonFootprint\Application\Process\EmissionFactor\Update\UpdateEmissionFactorsService;
use App\CarbonFootprint\Application\Process\ProcessAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Process\ProcessMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Process\ProcessRepositoryMother;
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
        $repository = ProcessRepositoryMother::inMemory();

        $process = ProcessMother::random();
        $repository->add($process);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, $emissionFactorGroupRepository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new EmissionFactorRow(
                    processId: (string) $process->id(),
                    processName: $process->name(),
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

        $persisted = $repository->find($process->id());

        $this->assertNotNull($persisted);
        $factor = $persisted->emissionFactorFor(2025, $emissionFactorGroup->id());
        $this->assertNotNull($factor);
        $this->assertSame('1.5', $factor->emissionFactor()->co2eq());
    }

    public function testRemovesEmissionFactorWhenRowIsEmpty(): void
    {
        $repository = ProcessRepositoryMother::inMemory();

        $emissionFactorGroup = EmissionFactorGroupMother::default();

        $process = ProcessMother::random();
        $ef = new EmissionFactor('1', '1', '1', '1', 2025, emissionFactorGroupId: $emissionFactorGroup->id(), unit: $process->unit());
        $process->setEmissionFactor($ef);
        $repository->add($process);

        $sut = $this->createSUT($repository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new EmissionFactorRow(
                    processId: (string) $process->id(),
                    processName: $process->name(),
                    emissionFactorGroupId: (string) $emissionFactorGroup->id(),
                ),
            ],
        );

        $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $persisted = $repository->find($process->id());

        $this->assertNotNull($persisted);
        $this->assertNull($persisted->emissionFactorFor(2025, $emissionFactorGroup->id()));
    }

    public function testUpdatesExistingEmissionFactor(): void
    {
        $repository = ProcessRepositoryMother::inMemory();

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $process = ProcessMother::random();
        $process->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2025, emissionFactorGroupId: $emissionFactorGroup->id(), unit: $process->unit()));
        $repository->add($process);

        $sut = $this->createSUT($repository, $emissionFactorGroupRepository);

        $sut(
            new UpdateEmissionFactorsRequest(
                year: 2025,
                rows: [
                    new EmissionFactorRow(
                        processId: (string) $process->id(),
                        processName: $process->name(),
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

        $persisted = $repository->find($process->id());

        $this->assertNotNull($persisted);
        $this->assertCount(1, $persisted->emissionFactors());
        $factor = $persisted->emissionFactorFor(2025, $emissionFactorGroup->id());
        $this->assertNotNull($factor);
        $this->assertSame('2.5', $factor->emissionFactor()->co2eq());
        $this->assertSame(Unit::Piece, $factor->emissionFactor()->unit());
    }

    public function testOwnerCannotUpdate(): void
    {
        $sut = $this->createSUT(ProcessRepositoryMother::inMemory());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new UpdateEmissionFactorsRequest(year: 2025), UserMother::fromUsernameAndRole('owner', Role::Owner));
    }

    private function createSUT(
        ProcessRepository $repository,
        ?EmissionFactorGroupRepository $emissionFactorGroupRepository = null,
    ): UpdateEmissionFactorsService {
        return new UpdateEmissionFactorsService(
            $repository,
            new ProcessAuthorizer(),
            $emissionFactorGroupRepository ?? EmissionFactorGroupRepositoryMother::inMemory(),
        );
    }
}
