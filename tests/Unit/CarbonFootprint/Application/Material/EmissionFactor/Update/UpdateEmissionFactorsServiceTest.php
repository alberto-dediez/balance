<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Material\EmissionFactor\Update;

use App\CarbonFootprint\Application\Material\EmissionFactor\EmissionFactorRow;
use App\CarbonFootprint\Application\Material\EmissionFactor\Update\UpdateEmissionFactorsRequest;
use App\CarbonFootprint\Application\Material\EmissionFactor\Update\UpdateEmissionFactorsService;
use App\CarbonFootprint\Application\Material\MaterialAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialRepositoryMother;
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
        $repository = MaterialRepositoryMother::inMemory();

        $material = MaterialMother::random();
        $repository->add($material);

        $sut = $this->createSUT($repository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new EmissionFactorRow(
                    materialId: (string) $material->id(),
                    materialName: $material->name(),
                    co2eq: '1.5',
                    co2: '1.0',
                    ch4: '0.2',
                    n2o: '0.3',
                    source: 'IPCC',
                ),
            ],
        );

        $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $persisted = $repository->find($material->id());

        $this->assertNotNull($persisted);
        $factor = $persisted->emissionFactorFor(2025);
        $this->assertNotNull($factor);
        $this->assertSame('1.5', $factor->newFactor()->co2eq());
    }

    public function testRemovesEmissionFactorWhenRowIsEmpty(): void
    {
        $repository = MaterialRepositoryMother::inMemory();

        $material = MaterialMother::random();
        $ef = new EmissionFactor('1', '1', '1', '1', 2025, emissionFactorGroupId: EmissionFactorGroupMother::default()->id());
        $material->setEmissionFactor($ef, $ef);
        $repository->add($material);

        $sut = $this->createSUT($repository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new EmissionFactorRow(
                    materialId: (string) $material->id(),
                    materialName: $material->name(),
                ),
            ],
        );

        $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $persisted = $repository->find($material->id());

        $this->assertNotNull($persisted);
        $this->assertNull($persisted->emissionFactorFor(2025));
    }

    private function createSUT(MaterialRepository $repository): UpdateEmissionFactorsService
    {
        return new UpdateEmissionFactorsService($repository, new MaterialAuthorizer());
    }
}
