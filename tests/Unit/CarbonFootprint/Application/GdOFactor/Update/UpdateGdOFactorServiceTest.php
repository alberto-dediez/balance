<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\GdOFactor\Update;

use App\CarbonFootprint\Application\GdOFactor\GdOFactorAuthorizer;
use App\CarbonFootprint\Application\GdOFactor\Update\EmissionFactorData;
use App\CarbonFootprint\Application\GdOFactor\Update\UpdateGdOFactorRequest;
use App\CarbonFootprint\Application\GdOFactor\Update\UpdateGdOFactorService;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorUpdated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateGdOFactorServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = GdOFactorRepositoryMother::inMemory();

        $gdOFactor = GdOFactorMother::random();

        $repository->add($gdOFactor);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository);

        $request = new UpdateGdOFactorRequest(
            gdOFactorId: $gdOFactor->id(),
            name: 'changedName',
        );

        $result = $sut($request, $user);

        $gdOFactorPersisted = $repository->find($result->gdOFactorId);

        $this->assertNotNull($gdOFactorPersisted);
        $this->assertEquals('changedName', $gdOFactorPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof GdOFactorUpdated);
        $this->assertTrue($gdOFactorPersisted->id()->equals($lastEvent->gdOFactorId()));
    }

    public function testUpdateSyncsEmissionFactors(): void
    {
        $repository = GdOFactorRepositoryMother::inMemory();

        $gdOFactor = GdOFactorMother::random();

        $repository->add($gdOFactor);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, $emissionFactorGroupRepository);

        $request = new UpdateGdOFactorRequest(
            gdOFactorId: $gdOFactor->id(),
            name: 'changedName',
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

        $gdOFactorPersisted = $repository->find($result->gdOFactorId);

        $this->assertNotNull($gdOFactorPersisted);
        $this->assertCount(1, $gdOFactorPersisted->emissionFactors());

        $persistedFactor = $gdOFactorPersisted->emissionFactorFor(2024, $emissionFactorGroup->id());

        $this->assertNotNull($persistedFactor);
        $this->assertSame('1.5', $persistedFactor->co2eq());
        $this->assertSame(Unit::KWh, $persistedFactor->unit());
    }

    public function testUpdateSyncsWellToTankFactors(): void
    {
        $repository = GdOFactorRepositoryMother::inMemory();

        $gdOFactor = GdOFactorMother::random();

        $repository->add($gdOFactor);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, $emissionFactorGroupRepository);

        $request = new UpdateGdOFactorRequest(
            gdOFactorId: $gdOFactor->id(),
            name: 'changedName',
            emissionFactors: [
                new EmissionFactorData(
                    year: 2024,
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

        $result = $sut($request, $user);

        $gdOFactorPersisted = $repository->find($result->gdOFactorId);

        $this->assertNotNull($gdOFactorPersisted);
        $this->assertCount(1, $gdOFactorPersisted->emissionFactors());

        $persistedFactor = $gdOFactorPersisted->wellToTankFactorFor(2024, $emissionFactorGroup->id());

        $this->assertNotNull($persistedFactor);
        $this->assertSame('0.61', $persistedFactor->co2eq());
        $this->assertSame('DEFRA', $persistedFactor->source());
        $this->assertSame(Unit::KWh, $persistedFactor->unit());
    }

    private function createSUT(
        GdOFactorRepository $repository,
        ?EmissionFactorGroupRepository $emissionFactorGroupRepository = null,
    ): UpdateGdOFactorService {
        return new UpdateGdOFactorService(
            gdOFactorRepository: $repository,
            authorizer: new GdOFactorAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository ?? EmissionFactorGroupRepositoryMother::inMemory(),
        );
    }
}
