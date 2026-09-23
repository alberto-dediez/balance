<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportFilter;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportStatus;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class CarbonFootprintReportRepositoryTest extends KernelTestCase
{
    abstract protected function createSUT(): CarbonFootprintReportRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testAddAndFind(): void
    {
        $repository = $this->createSUT();
        $report = CarbonFootprintReportMother::registeredFor();

        $this->assertNull($repository->find($report->id()));

        $repository->add($report);

        $recovered = $repository->find($report->id());
        $this->assertNotNull($recovered);
        $this->assertEquals($report->id(), $recovered->id());
    }

    public function testRemove(): void
    {
        $repository = $this->createSUT();
        $report = CarbonFootprintReportMother::registeredFor();
        $repository->add($report);

        $repository->remove($report);

        $this->assertNull($repository->find($report->id()));
    }

    public function testFindBySubject(): void
    {
        $repository = $this->createSUT();
        $repository->add(CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Property, 'footprint-1'));
        $repository->add(CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Company, 'footprint-1'));

        $found = $repository->findBySubject(CarbonFootprintReportSubjectType::Company, 'footprint-1');

        $this->assertNotNull($found);
        $this->assertSame(CarbonFootprintReportSubjectType::Company, $found->subjectType());
        $this->assertNull($repository->findBySubject(CarbonFootprintReportSubjectType::Event, 'footprint-1'));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();
        $accountOne = new AccountId('account-one');
        $accountTwo = new AccountId('account-two');

        $repository->add(CarbonFootprintReportMother::pendingReview(CarbonFootprintReportSubjectType::Property, 'footprint-1', $accountOne));
        $repository->add(CarbonFootprintReportMother::pendingReview(CarbonFootprintReportSubjectType::Company, 'footprint-2', $accountTwo));
        $repository->add(CarbonFootprintReportMother::generated(CarbonFootprintReportSubjectType::Event, 'footprint-3', $accountOne));

        $this->assertCount(3, $repository->findByFilter(new CarbonFootprintReportFilter()));
        $this->assertCount(2, $repository->findByFilter(new CarbonFootprintReportFilter(status: CarbonFootprintReportStatus::PendingReview)));
        $this->assertCount(1, $repository->findByFilter(new CarbonFootprintReportFilter(subjectType: CarbonFootprintReportSubjectType::Company)));
        $this->assertCount(2, $repository->findByFilter(new CarbonFootprintReportFilter(accountId: $accountOne)));
        $this->assertCount(1, $repository->findByFilter(new CarbonFootprintReportFilter(status: CarbonFootprintReportStatus::PendingReview, accountId: $accountOne)));
        $this->assertSame(2, $repository->totalResultsByFilter(new CarbonFootprintReportFilter(status: CarbonFootprintReportStatus::PendingReview)));
        $this->assertCount(1, $repository->findByFilter(new CarbonFootprintReportFilter(limit: 1)));
    }

    public function testFindByFilterOrdersBySubmittedAtLeavingUnsubmittedReportsFirstWhenAscending(): void
    {
        $repository = $this->createSUT();
        $unsubmitted = CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Property, 'footprint-1');
        $submitted = CarbonFootprintReportMother::pendingReview(CarbonFootprintReportSubjectType::Company, 'footprint-2');
        $repository->add($submitted);
        $repository->add($unsubmitted);

        $ascending = new CarbonFootprintReportFilter();
        $ascending->orderBy = 'submittedAt';
        $ascending->orderWay = 'ASC';

        $descending = new CarbonFootprintReportFilter();
        $descending->orderBy = 'submittedAt';
        $descending->orderWay = 'DESC';

        $this->assertEquals([$unsubmitted->id(), $submitted->id()], array_map(static fn($report) => $report->id(), $repository->findByFilter($ascending)));
        $this->assertEquals([$submitted->id(), $unsubmitted->id()], array_map(static fn($report) => $report->id(), $repository->findByFilter($descending)));
    }
}
