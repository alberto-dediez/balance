<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Notification;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCarbonFootprintReportRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprintReport\CarbonFootprintReportResultsRoute;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\Notifications\Application\Notification\Notify\SpySystemNotifier;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;

trait NotifierContextTrait
{
    private SpySystemNotifier $notifier;
    private InMemoryCarbonFootprintReportRepository $reportRepository;
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private InMemoryUserRepository $userRepository;
    private UserId $requesterId;

    private function initNotifierContext(): void
    {
        $this->notifier = new SpySystemNotifier();
        $this->reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $this->eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();
        $this->userRepository = new InMemoryUserRepository();
        $this->requesterId = UserId::generate();
        $this->userRepository->add(UserMother::fromUsernameAndRoleAndId('owner', Role::Owner, $this->requesterId));
    }

    private function resultsRoute(): CarbonFootprintReportResultsRoute
    {
        return new CarbonFootprintReportResultsRoute($this->eventCarbonFootprintRepository);
    }

    private function givenReport(CarbonFootprintReportSubjectType $subjectType, string $state = 'registeredFor'): CarbonFootprintReport
    {
        $carbonFootprintId = null;

        if (CarbonFootprintReportSubjectType::Event === $subjectType) {
            $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-1', 'account-id');
            $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);
            $carbonFootprintId = (string) $eventCarbonFootprint->id();
        }

        $report = CarbonFootprintReportMother::$state($subjectType, $carbonFootprintId, new AccountId('account-id'), $this->requesterId);
        $this->reportRepository->add($report);

        return $report;
    }
}
