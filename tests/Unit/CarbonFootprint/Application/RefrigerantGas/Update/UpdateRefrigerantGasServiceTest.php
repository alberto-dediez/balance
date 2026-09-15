<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\RefrigerantGas\Update;

use App\CarbonFootprint\Application\RefrigerantGas\RefrigerantGasAuthorizer;
use App\CarbonFootprint\Application\RefrigerantGas\Update\EmissionFactorData;
use App\CarbonFootprint\Application\RefrigerantGas\Update\UpdateRefrigerantGasRequest;
use App\CarbonFootprint\Application\RefrigerantGas\Update\UpdateRefrigerantGasService;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasUpdated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateRefrigerantGasServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = RefrigerantGasRepositoryMother::inMemory();

        $refrigerantGas = RefrigerantGasMother::random();

        $repository->add($refrigerantGas);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository);

        $request = new UpdateRefrigerantGasRequest(
            refrigerantGasId: $refrigerantGas->id(),
            name: 'changedName',
        );

        $result = $sut($request, $user);

        $refrigerantGasPersisted = $repository->find($result->refrigerantGasId);

        $this->assertNotNull($refrigerantGasPersisted);
        $this->assertEquals('changedName', $refrigerantGasPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof RefrigerantGasUpdated);
        $this->assertTrue($refrigerantGasPersisted->id()->equals($lastEvent->refrigerantGasId()));
    }

    public function testUpdateSyncsEmissionFactors(): void
    {
        $repository = RefrigerantGasRepositoryMother::inMemory();

        $refrigerantGas = RefrigerantGasMother::random();

        $repository->add($refrigerantGas);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, $emissionFactorGroupRepository);

        $request = new UpdateRefrigerantGasRequest(
            refrigerantGasId: $refrigerantGas->id(),
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

        $refrigerantGasPersisted = $repository->find($result->refrigerantGasId);

        $this->assertNotNull($refrigerantGasPersisted);
        $this->assertCount(1, $refrigerantGasPersisted->emissionFactors());

        $persistedFactor = $refrigerantGasPersisted->emissionFactorFor(2024, $emissionFactorGroup->id());

        $this->assertNotNull($persistedFactor);
        // co2eq is respected as provided by the user (non-zero), not recomputed from co2/ch4/n2o.
        $this->assertSame('1.5', $persistedFactor->co2eq());
        $this->assertSame(Unit::Kilogram, $persistedFactor->unit());
    }

    private function createSUT(
        RefrigerantGasRepository $repository,
        ?EmissionFactorGroupRepository $emissionFactorGroupRepository = null,
    ): UpdateRefrigerantGasService {
        return new UpdateRefrigerantGasService(
            refrigerantGasRepository: $repository,
            authorizer: new RefrigerantGasAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository ?? EmissionFactorGroupRepositoryMother::inMemory(),
        );
    }
}
