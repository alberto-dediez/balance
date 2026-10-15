<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Process\Update;

use App\CarbonFootprint\Application\Process\ProcessAuthorizer;
use App\CarbonFootprint\Application\Process\Update\EmissionFactorData;
use App\CarbonFootprint\Application\Process\Update\UpdateProcessRequest;
use App\CarbonFootprint\Application\Process\Update\UpdateProcessService;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;
use App\CarbonFootprint\Domain\Model\Process\ProcessTranslated;
use App\CarbonFootprint\Domain\Model\Process\ProcessUpdated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Process\ProcessMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Process\ProcessRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateProcessServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = ProcessRepositoryMother::inMemory();

        $process = ProcessMother::random();

        $repository->add($process);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateProcessRequest(
            processId: $process->id(),
            name: 'changedName',
            unit: Unit::Piece,
        );

        $result = $sut($request, $user);

        $processPersisted = $repository->find($result->processId);

        $this->assertNotNull($processPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ProcessUpdated);
        $this->assertTrue($processPersisted->id()->equals($lastEvent->processId()));
    }

    public function testUpdateWithLocaleTranslate(): void
    {
        $repository = ProcessRepositoryMother::inMemory(Locale::Spanish);

        $process = ProcessMother::random();

        $repository->add($process);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new UpdateProcessRequest(
            processId: $process->id(),
            name: 'changedName',
            unit: Unit::Piece,
        );

        $result = $sut($request, $user);

        $processPersisted = $repository->find($result->processId);

        $this->assertNotNull($processPersisted);
        $this->assertEquals('changedName', $processPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ProcessTranslated);
        $this->assertTrue($processPersisted->id()->equals($lastEvent->processId()));
    }

    public function testUpdateSyncsEmissionFactors(): void
    {
        $repository = ProcessRepositoryMother::inMemory();

        $process = ProcessMother::random();

        $repository->add($process);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, Locale::Spanish, $emissionFactorGroupRepository);

        $request = new UpdateProcessRequest(
            processId: $process->id(),
            name: 'changedName',
            unit: Unit::Piece,
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

        $processPersisted = $repository->find($result->processId);

        $this->assertNotNull($processPersisted);
        $this->assertCount(1, $processPersisted->emissionFactors());

        $persistedFactor = $processPersisted->emissionFactorFor(2024, $emissionFactorGroup->id());

        $this->assertNotNull($persistedFactor);
        $this->assertSame('1.5', $persistedFactor->emissionFactor()->co2eq());
        $this->assertSame(Unit::Piece, $persistedFactor->emissionFactor()->unit());
    }

    public function testUnitChangeRestampsEmissionFactors(): void
    {
        $repository = ProcessRepositoryMother::inMemory();

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $process = ProcessMother::random();
        $process->setEmissionFactor(new EmissionFactor('1.5', '1.0', '0.2', '0.3', 2024, emissionFactorGroupId: $emissionFactorGroup->id(), unit: Unit::Piece));
        $repository->add($process);

        $sut = $this->createSUT($repository, Locale::Spanish, $emissionFactorGroupRepository);

        $sut(
            new UpdateProcessRequest(
                processId: $process->id(),
                name: $process->name(),
                unit: Unit::KWh,
                emissionFactors: [
                    new EmissionFactorData(
                        year: 2024,
                        co2eq: '1.5',
                        co2: '1.0',
                        ch4: '0.2',
                        n2o: '0.3',
                        emissionFactorGroupId: (string) $emissionFactorGroup->id(),
                    ),
                ],
            ),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );

        $persisted = $repository->find($process->id());

        $this->assertNotNull($persisted);
        $this->assertSame(Unit::KWh, $persisted->unit());
        $this->assertSame(Unit::KWh, $persisted->emissionFactorFor(2024, $emissionFactorGroup->id())?->emissionFactor()->unit());
    }

    public function testThrowsWhenEmissionFactorGroupNotFound(): void
    {
        $repository = ProcessRepositoryMother::inMemory();
        $process = ProcessMother::random();
        $repository->add($process);

        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new UpdateProcessRequest(
                processId: $process->id(),
                name: 'changedName',
                unit: Unit::Piece,
                emissionFactors: [
                    new EmissionFactorData(year: 2024, co2eq: '1', co2: '1', ch4: '1', n2o: '1', emissionFactorGroupId: (string) ProcessId::generate()),
                ],
            ),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );
    }

    public function testOwnerCannotUpdate(): void
    {
        $repository = ProcessRepositoryMother::inMemory();
        $process = ProcessMother::random();
        $repository->add($process);

        $sut = $this->createSUT($repository);

        $this->expectException(UnauthorizedActionException::class);

        $sut(
            new UpdateProcessRequest(
                processId: $process->id(),
                name: 'changedName',
                unit: Unit::Piece,
            ),
            UserMother::fromUsernameAndRole('owner', Role::Owner),
        );
    }

    private function createSUT(
        ProcessRepository $repository,
        Locale $locale = Locale::Spanish,
        ?EmissionFactorGroupRepository $emissionFactorGroupRepository = null,
    ): UpdateProcessService {
        $emissionFactorGroupRepository ??= EmissionFactorGroupRepositoryMother::inMemory();

        FakeLocaleSetter::set($locale);

        return new UpdateProcessService(
            processRepository: $repository,
            authorizer: new ProcessAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository,
        );
    }
}
