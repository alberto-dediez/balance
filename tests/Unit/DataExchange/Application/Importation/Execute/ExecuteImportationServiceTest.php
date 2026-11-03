<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Application\Importation\Execute;

use App\DataExchange\Application\Importation\Execute\ExecuteImportationRequest;
use App\DataExchange\Application\Importation\Execute\ExecuteImportationService;
use App\DataExchange\Application\Importation\ExecuteSingle\ExecuteSingleRequest;
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
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\DataExchange\Application\Importation\ImportationAuthorizerMother;
use App\Tests\Unit\DataExchange\Domain\Model\Importation\ImportationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CommandBus\SluttyCommandHandler;
use App\Tests\Unit\Shared\Application\CommandBus\ThrowingCommandHandler;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ExecuteImportationServiceTest extends TestCase
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
            path: __DIR__ . '/../Register/superheroes.csv',
        );

        $importation = ImportationMother::fromNameAndFile('superheroes.csv', $importationFile);

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

        $request = new ExecuteImportationRequest(
            importationId: $importation->id(),
        );

        $result = $sut($request, $user);

        $this->assertNotEmpty((string) $result->importationId);
        $this->assertEquals(36, strlen((string) $result->importationId));

        $importationPersisted = $repository->find($result->importationId);

        $this->assertNotNull($importationPersisted);
        $this->assertTrue($importationPersisted->isInProgress());

        $commands = $this->spyCommandBus->commands();

        $this->assertCount(3, $commands);
        $this->assertTrue($commands[0]['command'] instanceof DeferredCommand);
        $this->assertTrue($commands[0]['command']->command() instanceof ExecuteSingleRequest);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ImportationExecutionStarted);
        $this->assertTrue($importationPersisted->id()->equals($lastEvent->importationId()));

        $this->assertSame(3, $repository->pendingRows($result->importationId));
        $this->assertCount(0, array_filter($this->spySubscriber->events(), fn(DomainEvent $event): bool => $event instanceof ImportationExecuted));
    }

    public function testAnImportationWithoutRowsIsExecutedAtOnce(): void
    {
        $importation = ImportationMother::fromNameAndFile('empty.csv', new ImportationFile(path: __DIR__ . '/empty.csv'));

        $repository = new InMemoryImportationRepository();
        $repository->add($importation);

        $sut = $this->createSUT($repository);

        $result = $sut(new ExecuteImportationRequest(importationId: $importation->id()), $this->ownerOf($importation));

        $importationPersisted = $repository->find($result->importationId);

        $this->assertNotNull($importationPersisted);
        $this->assertTrue($importationPersisted->isExecuted());
        $this->assertCount(0, $this->spyCommandBus->commands());
        $this->assertInstanceOf(ImportationExecuted::class, $this->spySubscriber->lastEvent());
    }

    public function testAnImportationAlreadyClosedByAnotherProcessDoesNotPublishTheExecutionAgain(): void
    {
        $importation = ImportationMother::fromNameAndFile('empty.csv', new ImportationFile(path: __DIR__ . '/empty.csv'));

        $repository = new InMemoryImportationRepository();
        $repository->add($importation);

        $this->assertTrue($repository->claimExecution($importation->id()));

        $sut = $this->createSUT($repository);

        $sut(new ExecuteImportationRequest(importationId: $importation->id()), $this->ownerOf($importation));

        $this->assertTrue($importation->isInProgress());
        $this->assertCount(0, array_filter($this->spySubscriber->events(), fn(DomainEvent $event): bool => $event instanceof ImportationExecuted));
    }

    public function testARowThatCannotBeQueuedIsRecordedAsFailed(): void
    {
        CommandBusDispatcher::instance()->reset();
        new ThrowingCommandHandler('queue down');

        $importation = ImportationMother::fromNameAndFile('superheroe.csv', new ImportationFile(path: __DIR__ . '/../ExecuteSingle/superheroe.csv'));

        $repository = new InMemoryImportationRepository();
        $repository->add($importation);

        $sut = $this->createSUT($repository);

        $result = $sut(new ExecuteImportationRequest(importationId: $importation->id()), $this->ownerOf($importation));

        $this->assertSame(0, $repository->pendingRows($result->importationId));
        $this->assertSame('queue down', $repository->getResult($result->importationId, 0)['error'] ?? null);

        $importationPersisted = $repository->find($result->importationId);

        $this->assertNotNull($importationPersisted);
        $this->assertTrue($importationPersisted->isExecuted());
    }

    public function testAnExecutedImportationCannotBeExecutedAgain(): void
    {
        $importation = ImportationMother::fromNameAndFileAndStatus('superheroes.csv', new ImportationFile(path: __DIR__ . '/../Register/superheroes.csv'), ImportationStatus::Executed);

        $repository = new InMemoryImportationRepository();
        $repository->add($importation);

        $sut = $this->createSUT($repository);

        try {
            $sut(new ExecuteImportationRequest(importationId: $importation->id()), $this->ownerOf($importation));
            $this->fail('An executed importation must not be executed again');
        } catch (UnauthorizedActionException) {
        }

        $this->assertCount(0, $this->spyCommandBus->commands());
    }

    public function testAnImportationInProgressCannotBeExecutedAgain(): void
    {
        $importation = ImportationMother::fromNameAndFileAndStatus('superheroes.csv', new ImportationFile(path: __DIR__ . '/../Register/superheroes.csv'), ImportationStatus::InProgress);

        $repository = new InMemoryImportationRepository();
        $repository->add($importation);

        $sut = $this->createSUT($repository);

        try {
            $sut(new ExecuteImportationRequest(importationId: $importation->id()), $this->ownerOf($importation));
            $this->fail('An importation in progress must not be executed again');
        } catch (UnauthorizedActionException) {
        }

        $this->assertCount(0, $this->spyCommandBus->commands());
        $this->assertCount(0, array_filter($this->spySubscriber->events(), fn(DomainEvent $event): bool => $event instanceof ImportationExecutionStarted));
        $this->assertSame(3, $repository->pendingRows($importation->id()));
    }

    public function testAStaleImportationAlreadyStartedByAnotherProcessIsNotExecutedAgain(): void
    {
        $importation = ImportationMother::fromNameAndFile('superheroes.csv', new ImportationFile(path: __DIR__ . '/../Register/superheroes.csv'));

        $repository = new InMemoryImportationRepository();
        $repository->add($importation);

        $this->assertTrue($repository->claimStart($importation->id()));

        $sut = $this->createSUT($repository);

        try {
            $sut(new ExecuteImportationRequest(importationId: $importation->id()), $this->ownerOf($importation));
            $this->fail('An importation already started by another process must not be executed again');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Importation already started', $exception->getMessage());
        }

        $this->assertFalse($importation->isInProgress());
        $this->assertCount(0, $this->spyCommandBus->commands());
        $this->assertCount(0, array_filter($this->spySubscriber->events(), fn(DomainEvent $event): bool => $event instanceof ImportationExecutionStarted));
        $this->assertSame(3, $repository->pendingRows($importation->id()));
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

        $request = new ExecuteImportationRequest(
            importationId: $importation->id(),
        );

        $sut($request, $user);
    }

    public function testIsHandle(): void
    {
        $repository = new InMemoryImportationRepository();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new ExecuteImportationRequest(
            importationId: ImportationId::generate(),
        ));

        $this->assertTrue($result);
    }

    private function ownerOf(Importation $importation): User
    {
        $accountId = $importation->accountId();
        self::assertNotNull($accountId);

        return UserMother::fromUsernameAndRoleAndAccount(
            username: 'owner',
            role: Role::Owner,
            accountId: $accountId,
        );
    }

    private function createSUT(InMemoryImportationRepository $repository): ExecuteImportationService
    {
        $authorizer = ImportationAuthorizerMother::create();

        $importationType = $this->createMock(ImportationType::class);
        $importationType->method('code')->willReturn('measurements');
        $importationType->method('commandBuilder')->willReturnCallback(fn(array $data) => new FakeImportRequest(
            containerId: $data['containerId'] ?? null,
            propertyId: $data['propertyId'] ?? null,
            wasteId: $data['wasteId'] ?? null,
            purpose: $data['purpose'] ?? null,
            wasteManagerId: $data['wasteManagerId'] ?? null,
            quantity: $data['quantity'] ?? null,
            load: $data['load'] ?? null,
            justification: $data['justification'] ?? null,
            unit: $data['unit'] ?? null,
            day: $data['day'] ?? null,
            reference  : $data['reference'] ?? null,
        ));

        $importationTypeFactory = new ImportationTypeFactory([
            $importationType,
        ]);

        return new ExecuteImportationService($repository, $importationTypeFactory, $authorizer);
    }
}

class FakeImportRequest implements Command
{
    public function __construct(
        public ?string $containerId,
        public ?string $propertyId,
        public ?string $wasteId,
        public ?string $purpose,
        public ?string $wasteManagerId,
        public ?string $quantity,
        public ?string $load,
        public ?string $justification,
        public ?string $unit,
        public ?string $day,
        public ?string $reference,
    ) {}
}
