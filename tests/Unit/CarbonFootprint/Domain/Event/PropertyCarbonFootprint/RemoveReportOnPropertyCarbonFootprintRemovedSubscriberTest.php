<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\PropertyCarbonFootprint;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintReport\Remove\RemoveCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Event\PropertyCarbonFootprint\RemoveReportOnPropertyCarbonFootprintRemovedSubscriber;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportFilter;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRemoved;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintReopened;
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

class RemoveReportOnPropertyCarbonFootprintRemovedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToRemoved(): void
    {
        [$sut] = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo(new PropertyCarbonFootprintRemoved(PropertyCarbonFootprintId::generate())));
        $this->assertFalse($sut->isSubscribedTo(new PropertyCarbonFootprintReopened(PropertyCarbonFootprintId::generate())));
    }

    public function testRemovesTheReportOfTheRemovedFootprintWhateverItsStatus(): void
    {
        $id = PropertyCarbonFootprintId::generate();
        [$sut, $reportRepository] = $this->createSUT();
        $report = CarbonFootprintReportMother::pendingReview(CarbonFootprintReportSubjectType::Property, (string) $id, new AccountId('account-id'));
        $reportRepository->add($report);

        $sut->handle(new PropertyCarbonFootprintRemoved($id));

        $this->assertNull($reportRepository->find($report->id()));
    }

    public function testDoesNothingWithoutReport(): void
    {
        [$sut, $reportRepository] = $this->createSUT();

        $sut->handle(new PropertyCarbonFootprintRemoved(PropertyCarbonFootprintId::generate()));

        $this->assertSame([], $reportRepository->findByFilter(new CarbonFootprintReportFilter()));
    }

    /** @return array{0: RemoveReportOnPropertyCarbonFootprintRemovedSubscriber, 1: InMemoryCarbonFootprintReportRepository} */
    private function createSUT(): array
    {
        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();

        $users = UserRepositoryMother::inMemory();
        $users->add(UserMother::adminUser());

        $authorizer = new CarbonFootprintReportAuthorizer();

        $sut = new RemoveReportOnPropertyCarbonFootprintRemovedSubscriber(
            new RemoveCarbonFootprintReportService($reportRepository, $authorizer, new InMemoryCarbonFootprintReportStorage()),
            $reportRepository,
            new SystemActorResolver($users),
        );

        return [$sut, $reportRepository];
    }
}
