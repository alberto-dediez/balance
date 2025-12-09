<?php

namespace App\Tests\Unit\DataExchange\Application\Importation\ExecuteSingle;

use App\DataExchange\Application\Importation\Execute\ExecuteImportationRequest;
use App\DataExchange\Application\Importation\Execute\ExecuteImportationResponse;
use App\DataExchange\Application\Importation\Execute\ExecuteImportationService;
use App\DataExchange\Application\Importation\ExecuteSingle\ExecuteSingleRequest;
use App\DataExchange\Application\Importation\ExecuteSingle\ExecuteSingleResponse;
use App\DataExchange\Application\Importation\ExecuteSingle\ExecuteSingleService;
use App\DataExchange\Domain\Model\Importation\Importation;
use App\DataExchange\Domain\Model\Importation\ImportationExecuted;
use App\DataExchange\Domain\Model\Importation\ImportationExecutionStarted;
use App\DataExchange\Domain\Model\Importation\ImportationFile;
use App\DataExchange\Domain\Model\Importation\ImportationId;
use App\DataExchange\Domain\Model\Importation\ImportationStatus;
use App\DataExchange\Domain\Model\Importation\ImportationType;
use App\DataExchange\Domain\Model\Importation\ImportationTypeFactory;
use App\DataExchange\Infrastructure\Persistence\InMemory\Repository\InMemoryImportationRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\DataExchange\Application\Importation\ImportationAuthorizerMother;
use App\Tests\Unit\DataExchange\Domain\Model\Importation\ImportationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CommandBus\FakeCommand;
use App\Tests\Unit\Shared\Application\CommandBus\SluttyCommandHandler;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Waste\Application\Measurement\Import\ImportMeasurementRequest;
use PHPUnit\Framework\TestCase;

class ExecuteSingleServiceTest extends TestCase
{
    private $spySubscriber;

    private $spyCommandBus;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        CommandBusDispatcher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
        $this->spyCommandBus = new SluttyCommandHandler();
    }

    public function testUseCase()
    {
        $importationFile = new ImportationFile(
            path: __DIR__ . '/superheroes.csv'
        );

        $importation = ImportationMother::fromNameAndFileAndStatus('superheroes.csv', $importationFile, ImportationStatus::InProgress);

        $repository = new InMemoryImportationRepository();
        $repository->add($importation);

        $user = UserMother::fromUsernameAndRoleAndAccount(
            username: 'owner',
            role: Role::Owner,
            accountId: $importation->accountId()
        );

        $sut = $this->createSUT($repository);

        $request = new ExecuteSingleRequest(
            importationId: $importation->id(),
            rowNumber: 0,
            command: new FakeCommand()
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ExecuteSingleResponse);
        $this->assertNotEmpty((string) $result->importationId);
        $this->assertEquals(36, strlen((string) $result->importationId));

        $importationPersisted = $repository->find($result->importationId);

        $this->assertNotNull($importationPersisted);
        $this->assertTrue($importationPersisted instanceof Importation);
        $this->assertTrue($importationPersisted->isInProgress());

        $commands = $this->spyCommandBus->commands();

        $this->assertCount(1, $commands);
        $this->assertTrue($commands[0]['command'] instanceof FakeCommand);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNull($lastEvent);
    }

    public function testUseCaseLastCommand()
    {
        $importationFile = new ImportationFile(
            path: __DIR__ . '/superheroe.csv'
        );

        $importation = ImportationMother::fromNameAndFileAndStatus('superheroe.csv', $importationFile, ImportationStatus::InProgress);

        $repository = new InMemoryImportationRepository();
        $repository->add($importation);

        $user = UserMother::fromUsernameAndRoleAndAccount(
            username: 'owner',
            role: Role::Owner,
            accountId: $importation->accountId()
        );

        $sut = $this->createSUT($repository);

        $request = new ExecuteSingleRequest(
            importationId: $importation->id(),
            rowNumber: 0,
            command: new FakeCommand()
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ExecuteSingleResponse);
        $this->assertNotEmpty((string) $result->importationId);
        $this->assertEquals(36, strlen((string) $result->importationId));

        $importationPersisted = $repository->find($result->importationId);

        $this->assertNotNull($importationPersisted);
        $this->assertTrue($importationPersisted instanceof Importation);
        $this->assertTrue($importationPersisted->isExecuted());

        $commands = $this->spyCommandBus->commands();

        $this->assertCount(1, $commands);
        $this->assertTrue($commands[0]['command'] instanceof FakeCommand);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ImportationExecuted);
        $this->assertTrue($importationPersisted->id()->equals($lastEvent->importationId()));
    }

    public function testUnauthorizedAccess()
    {
        $this->expectException(UnauthorizedActionException::class);

        $importation = ImportationMother::random();

        $repository = new InMemoryImportationRepository();
        $repository->add($importation);

        $user = UserMother::fromUsernameAndRoleAndAccount(
            username: 'owner',
            role: Role::Owner,
            accountId: AccountId::generate()
        );

        $sut = $this->createSUT($repository);

        $request = new ExecuteSingleRequest(
            importationId: $importation->id(),
            rowNumber: 0,
            command: new FakeCommand()
        );

        $sut($request, $user);
    }

    public function testIsHandle()
    {
        $repository = new InMemoryImportationRepository();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new ExecuteSingleRequest(
            importationId: ImportationId::generate(),
            rowNumber: 0,
            command: new FakeCommand()
        ));

        $this->assertTrue($result);
    }

    private function createSUT($repository)
    {
        $authorizer = ImportationAuthorizerMother::create();

        $importationType = $this->createMock(ImportationType::class);
        $importationType->method('code')->willReturn('measurements');
        $importationType->method('isAuthorized')->willReturn(true);

        $importationTypeFactory = new ImportationTypeFactory([
            $importationType
        ]);

        return new ExecuteSingleService($repository, $importationTypeFactory, $authorizer);
    }
}
