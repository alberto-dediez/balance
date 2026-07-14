<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprintReport\Register;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\Generate\GenerateEventCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\Register\RegisterEventCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\Register\RegisterEventCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportStatus;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CommandBus\SluttyCommandHandler;
use PHPUnit\Framework\TestCase;

class RegisterEventCarbonFootprintReportServiceTest extends TestCase
{
    private SluttyCommandHandler $spyCommandBus;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        CommandBusDispatcher::instance()->reset();

        $this->spyCommandBus = new SluttyCommandHandler();
    }

    public function testRegistersReportAndDispatchesGenerateCommand(): void
    {
        $eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();
        $reportRepository = EventCarbonFootprintReportRepositoryMother::inMemory();

        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount((string) EventId::generate(), 'account-id-test');
        $this->completeFootprint($eventCarbonFootprint);
        $eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $sut = new RegisterEventCarbonFootprintReportService($eventCarbonFootprintRepository, new EventCarbonFootprintAuthorizer(), $reportRepository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $response = ($sut)(new RegisterEventCarbonFootprintReportRequest($eventCarbonFootprint->id()), $user);

        $persisted = $reportRepository->find($response->reportId);
        $this->assertNotNull($persisted);
        $this->assertSame(EventCarbonFootprintReportStatus::Pending, $persisted->status());

        $commands = $this->spyCommandBus->commands();
        $this->assertCount(1, $commands);
        $this->assertInstanceOf(DeferredCommand::class, $commands[0]['command']);
        $this->assertInstanceOf(GenerateEventCarbonFootprintReportRequest::class, $commands[0]['command']->command());
    }

    public function testThrowsWhenEventCarbonFootprintIsNotCompleted(): void
    {
        $eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();
        $reportRepository = EventCarbonFootprintReportRepositoryMother::inMemory();

        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount((string) EventId::generate(), 'account-id-test');
        $eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $sut = new RegisterEventCarbonFootprintReportService($eventCarbonFootprintRepository, new EventCarbonFootprintAuthorizer(), $reportRepository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        $this->expectException(\RuntimeException::class);
        ($sut)(new RegisterEventCarbonFootprintReportRequest($eventCarbonFootprint->id()), $user);
    }

    public function testThrowsWhenReportAlreadyExists(): void
    {
        $eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();
        $reportRepository = EventCarbonFootprintReportRepositoryMother::inMemory();

        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount((string) EventId::generate(), 'account-id-test');
        $this->completeFootprint($eventCarbonFootprint);
        $eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $sut = new RegisterEventCarbonFootprintReportService($eventCarbonFootprintRepository, new EventCarbonFootprintAuthorizer(), $reportRepository);
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));

        ($sut)(new RegisterEventCarbonFootprintReportRequest($eventCarbonFootprint->id()), $user);

        $this->expectException(EventCarbonFootprintReportAlreadyExistsException::class);
        ($sut)(new RegisterEventCarbonFootprintReportRequest($eventCarbonFootprint->id()), $user);
    }

    public function testUnauthorizedAccessThrows(): void
    {
        $eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();
        $reportRepository = EventCarbonFootprintReportRepositoryMother::inMemory();

        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount((string) EventId::generate(), 'account-id-test');
        $this->completeFootprint($eventCarbonFootprint);
        $eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $sut = new RegisterEventCarbonFootprintReportService($eventCarbonFootprintRepository, new EventCarbonFootprintAuthorizer(), $reportRepository);
        $user = UserMother::fromUsernameAndRole('user', Role::User);

        $this->expectException(UnauthorizedActionException::class);
        ($sut)(new RegisterEventCarbonFootprintReportRequest($eventCarbonFootprint->id()), $user);
    }

    public function testEventCarbonFootprintNotFoundThrows(): void
    {
        $sut = new RegisterEventCarbonFootprintReportService(
            EventCarbonFootprintRepositoryMother::inMemory(),
            new EventCarbonFootprintAuthorizer(),
            EventCarbonFootprintReportRepositoryMother::inMemory(),
        );

        $this->expectException(\InvalidArgumentException::class);
        ($sut)(new RegisterEventCarbonFootprintReportRequest(EventCarbonFootprintMother::random()->id()), UserMother::adminUser());
    }

    private function completeFootprint(EventCarbonFootprint $eventCarbonFootprint): void
    {
        $eventCarbonFootprint->update(
            $eventCarbonFootprint->calculationPeriod(),
        );
        $eventCarbonFootprint->complete(
            totalEmissions: $eventCarbonFootprint->totalEmissions(),
            emissionsPerAttendee: $eventCarbonFootprint->emissionsPerAttendee(),
        );
    }
}
