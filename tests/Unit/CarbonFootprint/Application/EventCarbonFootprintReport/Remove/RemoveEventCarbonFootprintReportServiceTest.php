<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprintReport\Remove;

use App\CarbonFootprint\Application\EventCarbonFootprintReport\EventCarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\Remove\RemoveEventCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\Remove\RemoveEventCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRemoved;
use App\CarbonFootprint\Infrastructure\Storage\InMemoryEventCarbonFootprintReportStorage;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveEventCarbonFootprintReportServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRemovesFilesAndAggregateAndPublishesEvent(): void
    {
        $reportRepository = EventCarbonFootprintReportRepositoryMother::inMemory();
        $storage = new InMemoryEventCarbonFootprintReportStorage();

        $report = EventCarbonFootprintReportMother::pending(accountId: 'account-id');
        $esPath = $storage->store($report->id(), $report->accountId(), Locale::Spanish, $this->tempFile());
        $enPath = $storage->store($report->id(), $report->accountId(), Locale::English, $this->tempFile());
        $report->translate($esPath, Locale::Spanish);
        $report->translate($enPath, Locale::English);
        $report->generate();
        $reportRepository->add($report);

        $sut = new RemoveEventCarbonFootprintReportService($reportRepository, new EventCarbonFootprintReportAuthorizer(), $storage);

        ($sut)(new RemoveEventCarbonFootprintReportRequest($report->id()), $this->ownerUser());

        $this->assertNull($reportRepository->find($report->id()));
        $this->assertCount(0, $storage->paths());
        $this->assertInstanceOf(EventCarbonFootprintReportRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testUnauthorizedAccessThrows(): void
    {
        $reportRepository = EventCarbonFootprintReportRepositoryMother::inMemory();
        $storage = new InMemoryEventCarbonFootprintReportStorage();

        $report = EventCarbonFootprintReportMother::generated(accountId: 'account-id');
        $reportRepository->add($report);

        $sut = new RemoveEventCarbonFootprintReportService($reportRepository, new EventCarbonFootprintReportAuthorizer(), $storage);

        $this->expectException(UnauthorizedActionException::class);
        ($sut)(
            new RemoveEventCarbonFootprintReportRequest($report->id()),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    private function tempFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ecf-report-test-');
        assert(false !== $path);

        return $path;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
