<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\Remove;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintReport\Remove\RemoveCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Remove\RemoveCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRemoved;
use App\CarbonFootprint\Infrastructure\Storage\InMemoryCarbonFootprintReportStorage;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveCarbonFootprintReportServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRemovesFilesAndAggregateAndPublishesEvent(): void
    {
        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $storage = new InMemoryCarbonFootprintReportStorage();

        $report = CarbonFootprintReportMother::generatingPdf();
        $report->translate($storage->store($report->id(), $report->accountId(), Locale::Spanish, $this->tempFile()), Locale::Spanish);
        $report->translate($storage->store($report->id(), $report->accountId(), Locale::English, $this->tempFile()), Locale::English);
        $report->generate();
        $reportRepository->add($report);

        $sut = new RemoveCarbonFootprintReportService($reportRepository, new CarbonFootprintReportAuthorizer(), $storage);

        ($sut)(new RemoveCarbonFootprintReportRequest($report->id()), $this->ownerUser());

        $this->assertNull($reportRepository->find($report->id()));
        $this->assertCount(0, $storage->paths());
        $this->assertInstanceOf(CarbonFootprintReportRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testUnauthorizedAccessThrows(): void
    {
        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $report = CarbonFootprintReportMother::generated();
        $reportRepository->add($report);

        $sut = new RemoveCarbonFootprintReportService($reportRepository, new CarbonFootprintReportAuthorizer(), new InMemoryCarbonFootprintReportStorage());

        $this->expectException(UnauthorizedActionException::class);
        ($sut)(new RemoveCarbonFootprintReportRequest($report->id()), UserMother::fromUsernameAndRole('user', Role::User));
    }

    private function tempFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cfp-report-test-');
        assert(false !== $path);

        return $path;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
