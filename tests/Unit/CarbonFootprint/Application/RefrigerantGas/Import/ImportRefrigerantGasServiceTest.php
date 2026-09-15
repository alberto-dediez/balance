<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\RefrigerantGas\Import;

use App\CarbonFootprint\Application\RefrigerantGas\Import\ImportRefrigerantGasRequest;
use App\CarbonFootprint\Application\RefrigerantGas\Import\ImportRefrigerantGasService;
use App\CarbonFootprint\Application\RefrigerantGas\RefrigerantGasAuthorizer;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ImportRefrigerantGasServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testCreatesNewRefrigerantGasByNameWhenNoneIsSelected(): void
    {
        $repository = RefrigerantGasRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $request = new ImportRefrigerantGasRequest(
            name: 'R-410A',
            year: '2024',
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.3',
            n2o: '0.2',
            source: 'IPCC',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $refrigerantGas = $repository->find($result->refrigerantGasId);

        $this->assertNotNull($refrigerantGas);
        $this->assertEquals('R-410A', $refrigerantGas->name());

        $emissionFactor = $refrigerantGas->emissionFactorFor(2024, EmissionFactorGroupMother::defaultId());

        $this->assertNotNull($emissionFactor);
        $this->assertEquals('1.5', $emissionFactor->co2eq());
        $this->assertEquals(Unit::Kilogram, $emissionFactor->unit());
    }

    public function testExplicitRefrigerantGasOnlyUpdatesEmissionFactor(): void
    {
        $repository = RefrigerantGasRepositoryMother::inMemory();
        $existing = RefrigerantGasMother::from(name: 'R-410A', id: (string) RefrigerantGasId::generate());
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportRefrigerantGasRequest(
            refrigerantGas: (string) $existing->id(),
            name: 'Otro nombre',
            year: '2025',
            co2eq: '3.0',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $refrigerantGas = $repository->find($result->refrigerantGasId);

        $this->assertNotNull($refrigerantGas);
        $this->assertEquals('R-410A', $refrigerantGas->name());
        $this->assertEquals('3.0', $refrigerantGas->emissionFactorFor(2025, EmissionFactorGroupMother::defaultId())?->co2eq());
    }

    public function testThrowsWhenNeitherRefrigerantGasNorNameProvided(): void
    {
        $repository = RefrigerantGasRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportRefrigerantGasRequest(year: '2024'), UserMother::adminUser());
    }

    public function testThrowsWhenYearIsInvalid(): void
    {
        $repository = RefrigerantGasRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportRefrigerantGasRequest(name: 'R-410A'), UserMother::adminUser());
    }

    public function testThrowsWhenExplicitRefrigerantGasNotFound(): void
    {
        $repository = RefrigerantGasRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new ImportRefrigerantGasRequest(
                refrigerantGas: (string) RefrigerantGasId::generate(),
                year: '2024',
            ),
            UserMother::adminUser(),
        );
    }

    public function testIsHandled(): void
    {
        $repository = RefrigerantGasRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $this->assertTrue($sut->isHandled(new ImportRefrigerantGasRequest(name: 'R-410A', year: '2024')));
    }

    private function createSUT(RefrigerantGasRepository $repository): ImportRefrigerantGasService
    {
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add(EmissionFactorGroupMother::default());

        return new ImportRefrigerantGasService(
            refrigerantGasRepository: $repository,
            authorizer: new RefrigerantGasAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository,
        );
    }
}
