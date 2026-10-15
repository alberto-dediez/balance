<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Process\Import;

use App\CarbonFootprint\Application\Process\Import\ImportProcessRequest;
use App\CarbonFootprint\Application\Process\Import\ImportProcessService;
use App\CarbonFootprint\Application\Process\ProcessAuthorizer;
use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Process\ProcessMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Process\ProcessRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class ImportProcessServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();
        FakeLocaleSetter::set(Locale::Spanish);
    }

    public function testCreatesNewProcessByNameWithDefaults(): void
    {
        $repository = ProcessRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository);

        $request = new ImportProcessRequest(
            name: 'Mecanizado de pieza',
            year: '2024',
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.3',
            n2o: '0.2',
            source: 'IPCC',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $process = $repository->find($result->processId);

        $this->assertNotNull($process);
        $this->assertEquals('Mecanizado de pieza', $process->name());
        $this->assertEquals(Unit::Piece, $process->unit());

        $emissionFactor = $process->emissionFactorFor(2024, EmissionFactorGroupMother::defaultId());

        $this->assertNotNull($emissionFactor);
        $this->assertEquals('1.5', $emissionFactor->emissionFactor()->co2eq());
        $this->assertEquals(Unit::Piece, $emissionFactor->emissionFactor()->unit());
    }

    public function testCreatesNewProcessWithMappedUnit(): void
    {
        $repository = ProcessRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository);

        $result = $sut(
            new ImportProcessRequest(
                name: 'Envasado',
                unit: Unit::KWh->value,
                year: '2024',
                co2eq: '1.5',
                co2: '1.0',
                ch4: '0.3',
                n2o: '0.2',
                emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
            ),
            UserMother::adminUser(),
        );

        $process = $repository->find($result->processId);

        $this->assertNotNull($process);
        $this->assertEquals(Unit::KWh, $process->unit());
    }

    public function testExplicitProcessOnlyUpdatesEmissionFactor(): void
    {
        $repository = ProcessRepositoryMother::inMemory(Locale::Spanish);
        $existing = ProcessMother::from(name: 'Mecanizado de pieza', id: (string) ProcessId::generate());
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportProcessRequest(
            process: (string) $existing->id(),
            name: 'Otro nombre',
            year: '2025',
            co2eq: '3.0',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $process = $repository->find($result->processId);

        $this->assertNotNull($process);
        $this->assertEquals('Mecanizado de pieza', $process->name());
        $this->assertEquals('3.0', $process->emissionFactorFor(2025, EmissionFactorGroupMother::defaultId())?->emissionFactor()->co2eq());
    }

    public function testThrowsWhenNeitherProcessNorNameProvided(): void
    {
        $sut = $this->createSUT(ProcessRepositoryMother::inMemory(Locale::Spanish));

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportProcessRequest(year: '2024', emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId()), UserMother::adminUser());
    }

    public function testThrowsWhenEmissionFactorGroupIsMissing(): void
    {
        $sut = $this->createSUT(ProcessRepositoryMother::inMemory(Locale::Spanish));

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportProcessRequest(name: 'Mecanizado de pieza', year: '2024', co2eq: '1.5'), UserMother::adminUser());
    }

    public function testThrowsWhenYearIsInvalid(): void
    {
        $sut = $this->createSUT(ProcessRepositoryMother::inMemory(Locale::Spanish));

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportProcessRequest(name: 'Mecanizado de pieza'), UserMother::adminUser());
    }

    public function testThrowsWhenExplicitProcessNotFound(): void
    {
        $sut = $this->createSUT(ProcessRepositoryMother::inMemory(Locale::Spanish));

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new ImportProcessRequest(
                process: (string) ProcessId::generate(),
                year: '2024',
            ),
            UserMother::adminUser(),
        );
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(ProcessRepositoryMother::inMemory(Locale::Spanish));

        $this->assertTrue($sut->isHandled(new ImportProcessRequest(name: 'Mecanizado de pieza', year: '2024')));
    }

    private function createSUT(ProcessRepository $repository): ImportProcessService
    {
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add(EmissionFactorGroupMother::default());

        return new ImportProcessService(
            processRepository: $repository,
            authorizer: new ProcessAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository,
        );
    }
}
