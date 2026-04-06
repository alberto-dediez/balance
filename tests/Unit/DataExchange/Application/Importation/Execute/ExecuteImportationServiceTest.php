<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Application\Importation\Execute;

use App\DataExchange\Application\Importation\Execute\ExecuteImportationRequest;
use App\DataExchange\Application\Importation\Execute\ExecuteImportationService;
use App\DataExchange\Application\Importation\ExecuteSingle\ExecuteSingleRequest;
use App\DataExchange\Domain\Model\Importation\ImportationExecutionStarted;
use App\DataExchange\Domain\Model\Importation\ImportationFile;
use App\DataExchange\Domain\Model\Importation\ImportationId;
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
use App\Tests\Unit\Shared\Application\CommandBus\SluttyCommandHandler;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Waste\Application\Measurement\Import\ImportMeasurementRequest;
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

    private function createSUT(InMemoryImportationRepository $repository): ExecuteImportationService
    {
        $authorizer = ImportationAuthorizerMother::create();

        $importationType = $this->createMock(ImportationType::class);
        $importationType->method('code')->willReturn('measurements');
        $importationType->method('commandBuilder')->willReturnCallback(fn(array $data): ImportMeasurementRequest => new ImportMeasurementRequest(
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
