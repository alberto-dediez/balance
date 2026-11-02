<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Application\Importation\ExecuteSingle;

use App\DataExchange\Application\Importation\ExecuteSingle\ExecuteSingleRequest;
use App\DataExchange\Application\Importation\ExecuteSingle\ExecuteSingleService;
use App\DataExchange\Domain\Model\Importation\ImportationExecuted;
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
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\DataExchange\Application\Importation\ImportationAuthorizerMother;
use App\Tests\Unit\DataExchange\Domain\Model\Importation\ImportationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CommandBus\FakeCommand;
use App\Tests\Unit\Shared\Application\CommandBus\SluttyCommandHandler;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ExecuteSingleServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    private SluttyCommandHandler $spyCommandBus;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        CommandBusDispatcher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
        $this->spyCommandBus = new SluttyCommandHandler();
    }

    public function testUseCase(): void
    {
        $importationFile = new ImportationFile(
            path: __DIR__ . '/superheroes.csv',
        );

        $importation = ImportationMother::fromNameAndFileAndStatus('superheroes.csv', $importationFile, ImportationStatus::InProgress);

        $repository = new InMemoryImportationRepository();
        $repository->add($importation);

        $accountId = $importation->accountId();
        self::assertNotNull($accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount(
            username: 'owner',
            role: Role::Owner,
            accountId: $accountId,
        );

        $sut = $this->createSUT($repository);

        $request = new ExecuteSingleRequest(
            importationId: $importation->id(),
            rowNumber: 0,
            command: new FakeCommand(),
        );

        $result = $sut($request, $user);

        $this->assertNotEmpty((string) $result->importationId);
        $this->assertEquals(36, strlen((string) $result->importationId));

        $importationPersisted = $repository->find($result->importationId);

        $this->assertNotNull($importationPersisted);
        $this->assertTrue($importationPersisted->isInProgress());

        $commands = $this->spyCommandBus->commands();

        $this->assertCount(1, $commands);
        $this->assertTrue($commands[0]['command'] instanceof FakeCommand);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNull($lastEvent);
    }

    public function testUseCaseLastCommand(): void
    {
        $importationFile = new ImportationFile(
            path: __DIR__ . '/superheroe.csv',
        );

        $importation = ImportationMother::fromNameAndFileAndStatus('superheroe.csv', $importationFile, ImportationStatus::InProgress);

        $repository = new InMemoryImportationRepository();
        $repository->add($importation);

        $accountId = $importation->accountId();
        self::assertNotNull($accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount(
            username: 'owner',
            role: Role::Owner,
            accountId: $accountId,
        );

        $sut = $this->createSUT($repository);

        $request = new ExecuteSingleRequest(
            importationId: $importation->id(),
            rowNumber: 0,
            command: new FakeCommand(),
        );

        $result = $sut($request, $user);

        $this->assertNotEmpty((string) $result->importationId);
        $this->assertEquals(36, strlen((string) $result->importationId));

        $importationPersisted = $repository->find($result->importationId);

        $this->assertNotNull($importationPersisted);
        $this->assertTrue($importationPersisted->isExecuted());

        $commands = $this->spyCommandBus->commands();

        $this->assertCount(1, $commands);
        $this->assertTrue($commands[0]['command'] instanceof FakeCommand);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ImportationExecuted);
        $this->assertTrue($importationPersisted->id()->equals($lastEvent->importationId()));
    }

    public function testTheImportationFinishesOnlyWithTheLastRow(): void
    {
        $importation = ImportationMother::fromNameAndFileAndStatus('superheroes.csv', new ImportationFile(path: __DIR__ . '/superheroes.csv'), ImportationStatus::InProgress);

        $repository = new InMemoryImportationRepository();
        $repository->add($importation);

        $accountId = $importation->accountId();
        self::assertNotNull($accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount(
            username: 'owner',
            role: Role::Owner,
            accountId: $accountId,
        );

        $sut = $this->createSUT($repository);

        $sut(new ExecuteSingleRequest(importationId: $importation->id(), rowNumber: 0, command: new FakeCommand()), $user);
        $sut(new ExecuteSingleRequest(importationId: $importation->id(), rowNumber: 1, command: new FakeCommand()), $user);

        $importationPersisted = $repository->find($importation->id());

        $this->assertNotNull($importationPersisted);
        $this->assertTrue($importationPersisted->isInProgress());
        $this->assertSame(1, $repository->pendingRows($importation->id()));
        $this->assertCount(0, $this->executedEvents());

        $sut(new ExecuteSingleRequest(importationId: $importation->id(), rowNumber: 2, command: new FakeCommand()), $user);

        $this->assertTrue($importationPersisted->isExecuted());
        $this->assertCount(1, $this->executedEvents());

        $sut(new ExecuteSingleRequest(importationId: $importation->id(), rowNumber: 2, command: new FakeCommand()), $user);

        $this->assertCount(1, $this->executedEvents());
    }

    public function testAStaleImportationAlreadyClosedByAnotherProcessDoesNotPublishTheExecutionAgain(): void
    {
        $importation = ImportationMother::fromNameAndFileAndStatus('superheroe.csv', new ImportationFile(path: __DIR__ . '/superheroe.csv'), ImportationStatus::InProgress);

        $repository = new InMemoryImportationRepository();
        $repository->add($importation);

        $this->assertTrue($repository->claimExecution($importation->id()));

        $accountId = $importation->accountId();
        self::assertNotNull($accountId);
        $user = UserMother::fromUsernameAndRoleAndAccount(
            username: 'owner',
            role: Role::Owner,
            accountId: $accountId,
        );

        $sut = $this->createSUT($repository);

        $sut(new ExecuteSingleRequest(importationId: $importation->id(), rowNumber: 0, command: new FakeCommand()), $user);

        $this->assertSame(0, $repository->pendingRows($importation->id()));
        $this->assertTrue($importation->isInProgress());
        $this->assertCount(0, $this->executedEvents());
    }

    public function testUnauthorizedAccess(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $importation = ImportationMother::random();

        $repository = new InMemoryImportationRepository();
        $repository->add($importation);

        $user = UserMother::fromUsernameAndRoleAndAccount(
            username: 'owner',
            role: Role::Owner,
            accountId: AccountId::generate(),
        );

        $sut = $this->createSUT($repository);

        $request = new ExecuteSingleRequest(
            importationId: $importation->id(),
            rowNumber: 0,
            command: new FakeCommand(),
        );

        $sut($request, $user);
    }

    public function testIsHandle(): void
    {
        $repository = new InMemoryImportationRepository();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new ExecuteSingleRequest(
            importationId: ImportationId::generate(),
            rowNumber: 0,
            command: new FakeCommand(),
        ));

        $this->assertTrue($result);
    }

    /** @return DomainEvent[] */
    private function executedEvents(): array
    {
        return array_values(array_filter($this->spySubscriber->events(), fn(DomainEvent $event): bool => $event instanceof ImportationExecuted));
    }

    private function createSUT(InMemoryImportationRepository $repository): ExecuteSingleService
    {
        $authorizer = ImportationAuthorizerMother::create();

        $importationType = $this->createMock(ImportationType::class);
        $importationType->method('code')->willReturn('measurements');
        $importationType->method('isAuthorized')->willReturn(true);

        $importationTypeFactory = new ImportationTypeFactory([
            $importationType,
        ]);

        return new ExecuteSingleService($repository, $importationTypeFactory, $authorizer);
    }
}
