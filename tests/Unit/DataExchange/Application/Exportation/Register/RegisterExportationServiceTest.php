<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Application\Exportation\Register;

use App\DataExchange\Application\Exportation\Execute\ExecuteExportationRequest;
use App\DataExchange\Application\Exportation\Register\RegisterExportationRequest;
use App\DataExchange\Application\Exportation\Register\RegisterExportationService;
use App\DataExchange\Domain\Model\Exportation\ExportationFormat;
use App\DataExchange\Domain\Model\Exportation\ExportationRegistered;
use App\DataExchange\Domain\Model\Exportation\ExportationStatus;
use App\DataExchange\Domain\Model\Exportation\ExportationTypeFactory;
use App\DataExchange\Infrastructure\Persistence\InMemory\Repository\InMemoryExportationRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\DataExchange\Application\Exportation\FakeExportationType;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CommandBus\SluttyCommandHandler;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterExportationServiceTest extends TestCase
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
        $repository = new InMemoryExportationRepository();
        $factory = new ExportationTypeFactory([new FakeExportationType()]);

        $sut = new RegisterExportationService($repository, $factory, 7);

        $user = UserMother::fromUsernameAndRoleAndAccount('admin', Role::Admin, AccountId::generate());

        $request = new RegisterExportationRequest(
            type: 'users',
            format: ExportationFormat::Xlsx,
            columns: ['username'],
            filter: new \stdClass(),
            accountId: $user->accountId(),
        );

        $response = $sut($request, $user);

        $persisted = $repository->find($response->exportationId);

        $this->assertNotNull($persisted);
        $this->assertSame(ExportationStatus::Started, $persisted->status());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(ExportationRegistered::class, $lastEvent);
        $this->assertTrue($persisted->id()->equals($lastEvent->exportationId()));

        $commands = $this->spyCommandBus->commands();
        $this->assertCount(1, $commands);
        $this->assertInstanceOf(DeferredCommand::class, $commands[0]['command']);
        $this->assertInstanceOf(ExecuteExportationRequest::class, $commands[0]['command']->command());
    }

    public function testUnauthorizedType(): void
    {
        $this->expectException(\RuntimeException::class);

        $repository = new InMemoryExportationRepository();
        $factory = new ExportationTypeFactory([new FakeExportationType(authorized: false)]);

        $sut = new RegisterExportationService($repository, $factory, 7);

        $user = UserMother::fromUsernameAndRoleAndAccount('admin', Role::Admin, AccountId::generate());

        $sut(
            new RegisterExportationRequest(
                type: 'users',
                format: ExportationFormat::Xlsx,
                columns: ['username'],
                filter: new \stdClass(),
                accountId: $user->accountId(),
            ),
            $user,
        );
    }

    public function testIsHandled(): void
    {
        $sut = new RegisterExportationService(
            new InMemoryExportationRepository(),
            new ExportationTypeFactory([new FakeExportationType()]),
            7,
        );

        $this->assertTrue($sut->isHandled(new RegisterExportationRequest(
            type: 'users',
            format: ExportationFormat::Xlsx,
            columns: ['username'],
            filter: new \stdClass(),
        )));
    }
}
