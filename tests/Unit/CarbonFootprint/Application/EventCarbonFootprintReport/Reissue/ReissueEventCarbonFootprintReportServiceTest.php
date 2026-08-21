<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprintReport\Reissue;

use App\CarbonFootprint\Application\EventCarbonFootprintReport\EventCarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\Generate\GenerateEventCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\Reissue\ReissueEventCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\Reissue\ReissueEventCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportStatus;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CommandBus\SluttyCommandHandler;
use PHPUnit\Framework\TestCase;

class ReissueEventCarbonFootprintReportServiceTest extends TestCase
{
    private SluttyCommandHandler $spyCommandBus;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        CommandBusDispatcher::instance()->reset();

        $this->spyCommandBus = new SluttyCommandHandler();
    }

    public function testReissuesReportAndDispatchesGenerateCommand(): void
    {
        $reportRepository = EventCarbonFootprintReportRepositoryMother::inMemory();
        $report = EventCarbonFootprintReportMother::generated(accountId: 'account-id-test');
        $reportRepository->add($report);

        $sut = new ReissueEventCarbonFootprintReportService($reportRepository, new EventCarbonFootprintReportAuthorizer());

        ($sut)(new ReissueEventCarbonFootprintReportRequest($report->id()), $this->owner());

        $persisted = $reportRepository->find($report->id());
        $this->assertNotNull($persisted);
        $this->assertSame(EventCarbonFootprintReportStatus::Pending, $persisted->status());

        $commands = $this->spyCommandBus->commands();
        $this->assertCount(1, $commands);
        $this->assertInstanceOf(DeferredCommand::class, $commands[0]['command']);
        $this->assertInstanceOf(GenerateEventCarbonFootprintReportRequest::class, $commands[0]['command']->command());
    }

    public function testDoesNothingWhenTheReportIsAlreadyPending(): void
    {
        $reportRepository = EventCarbonFootprintReportRepositoryMother::inMemory();
        $report = EventCarbonFootprintReportMother::pending(accountId: 'account-id-test');
        $reportRepository->add($report);

        $sut = new ReissueEventCarbonFootprintReportService($reportRepository, new EventCarbonFootprintReportAuthorizer());

        ($sut)(new ReissueEventCarbonFootprintReportRequest($report->id()), $this->owner());

        $this->assertCount(0, $this->spyCommandBus->commands());
    }

    public function testReissuesAFailedReport(): void
    {
        $reportRepository = EventCarbonFootprintReportRepositoryMother::inMemory();
        $report = EventCarbonFootprintReportMother::failed(accountId: 'account-id-test');
        $reportRepository->add($report);

        $sut = new ReissueEventCarbonFootprintReportService($reportRepository, new EventCarbonFootprintReportAuthorizer());

        ($sut)(new ReissueEventCarbonFootprintReportRequest($report->id()), $this->owner());

        $persisted = $reportRepository->find($report->id());
        $this->assertNotNull($persisted);
        $this->assertSame(EventCarbonFootprintReportStatus::Pending, $persisted->status());
        $this->assertNull($persisted->errorMessage());
    }

    public function testUnauthorizedAccessFromAnotherAccountThrows(): void
    {
        $reportRepository = EventCarbonFootprintReportRepositoryMother::inMemory();
        $report = EventCarbonFootprintReportMother::generated(accountId: 'account-id-test');
        $reportRepository->add($report);

        $sut = new ReissueEventCarbonFootprintReportService($reportRepository, new EventCarbonFootprintReportAuthorizer());

        $this->expectException(UnauthorizedActionException::class);
        ($sut)(
            new ReissueEventCarbonFootprintReportRequest($report->id()),
            UserMother::fromUsernameAndRoleAndAccount('other', Role::Owner, new AccountId('another-account-id')),
        );
    }

    public function testReportNotFoundThrows(): void
    {
        $sut = new ReissueEventCarbonFootprintReportService(
            EventCarbonFootprintReportRepositoryMother::inMemory(),
            new EventCarbonFootprintReportAuthorizer(),
        );

        $this->expectException(\InvalidArgumentException::class);
        ($sut)(new ReissueEventCarbonFootprintReportRequest(EventCarbonFootprintReportId::generate()), $this->owner());
    }

    private function owner(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
