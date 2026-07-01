<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ElectricityMarketer\EmissionFactor\Update;

use App\CarbonFootprint\Application\ElectricityMarketer\ElectricityMarketerAuthorizer;
use App\CarbonFootprint\Application\ElectricityMarketer\EmissionFactor\EmissionFactorRow;
use App\CarbonFootprint\Application\ElectricityMarketer\EmissionFactor\Update\UpdateEmissionFactorsRequest;
use App\CarbonFootprint\Application\ElectricityMarketer\EmissionFactor\Update\UpdateEmissionFactorsService;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
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
        $repository = ElectricityMarketerRepositoryMother::inMemory();

        $electricityMarketer = ElectricityMarketerMother::random();
        $repository->add($electricityMarketer);

        $sut = $this->createSUT($repository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new EmissionFactorRow(
                    electricityMarketerId: (string) $electricityMarketer->id(),
                    electricityMarketerName: $electricityMarketer->name(),
                    co2eq: '1.5',
                    co2: '1.0',
                    ch4: '0.2',
                    n2o: '0.3',
                    source: 'IPCC',
                ),
            ],
        );

        $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $persisted = $repository->find($electricityMarketer->id());

        $this->assertNotNull($persisted);
        $factor = $persisted->emissionFactorFor(2025);
        $this->assertNotNull($factor);
        $this->assertSame('1.5', $factor->co2eq());
    }

    public function testRemovesEmissionFactorWhenRowIsEmpty(): void
    {
        $repository = ElectricityMarketerRepositoryMother::inMemory();

        $electricityMarketer = ElectricityMarketerMother::random();
        $electricityMarketer->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2025, emissionFactorGroupId: EmissionFactorGroupMother::default()->id()));
        $repository->add($electricityMarketer);

        $sut = $this->createSUT($repository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new EmissionFactorRow(
                    electricityMarketerId: (string) $electricityMarketer->id(),
                    electricityMarketerName: $electricityMarketer->name(),
                ),
            ],
        );

        $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $persisted = $repository->find($electricityMarketer->id());

        $this->assertNotNull($persisted);
        $this->assertNull($persisted->emissionFactorFor(2025));
    }

    public function testIsHandled(): void
    {
        $repository = ElectricityMarketerRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->assertTrue($sut->isHandled(new UpdateEmissionFactorsRequest(year: 2025)));
    }

    private function createSUT(ElectricityMarketerRepository $repository): UpdateEmissionFactorsService
    {
        return new UpdateEmissionFactorsService($repository, new ElectricityMarketerAuthorizer());
    }
}
