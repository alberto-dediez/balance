<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\EventCarbonFootprint;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintReport\Remove\RemoveCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Event\EventCarbonFootprint\RemoveReportOnEventCarbonFootprintRemovedSubscriber;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportFilter;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRemoved;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintReopened;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCarbonFootprintReportRepository;
use App\CarbonFootprint\Infrastructure\Storage\InMemoryCarbonFootprintReportStorage;
use App\Security\Application\User\SystemActor\SystemActorResolver;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class RemoveReportOnEventCarbonFootprintRemovedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToRemoved(): void
    {
        [$sut] = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo(new EventCarbonFootprintRemoved(EventCarbonFootprintId::generate())));
        $this->assertFalse($sut->isSubscribedTo(new EventCarbonFootprintReopened(EventCarbonFootprintId::generate())));
    }

    public function testRemovesTheReportOfTheRemovedFootprintWhateverItsStatus(): void
    {
        $id = EventCarbonFootprintId::generate();
        [$sut, $reportRepository] = $this->createSUT();
        $report = CarbonFootprintReportMother::pendingReview(CarbonFootprintReportSubjectType::Event, (string) $id, new AccountId('account-id'));
        $reportRepository->add($report);

        $sut->handle(new EventCarbonFootprintRemoved($id));

        $this->assertNull($reportRepository->find($report->id()));
    }

    public function testDoesNothingWithoutReport(): void
    {
        [$sut, $reportRepository] = $this->createSUT();

        $sut->handle(new EventCarbonFootprintRemoved(EventCarbonFootprintId::generate()));

        $this->assertSame([], $reportRepository->findByFilter(new CarbonFootprintReportFilter()));
    }

    /** @return array{0: RemoveReportOnEventCarbonFootprintRemovedSubscriber, 1: InMemoryCarbonFootprintReportRepository} */
    private function createSUT(): array
    {
        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();

        $users = UserRepositoryMother::inMemory();
        $users->add(UserMother::adminUser());

        $authorizer = new CarbonFootprintReportAuthorizer();

        $sut = new RemoveReportOnEventCarbonFootprintRemovedSubscriber(
            new RemoveCarbonFootprintReportService($reportRepository, $authorizer, new InMemoryCarbonFootprintReportStorage()),
            $reportRepository,
            new SystemActorResolver($users),
        );

        return [$sut, $reportRepository];
    }
}
