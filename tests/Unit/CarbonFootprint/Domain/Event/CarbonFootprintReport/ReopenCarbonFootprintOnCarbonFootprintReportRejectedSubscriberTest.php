<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Event\CarbonFootprintReport;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportSubjects;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Reopen\ReopenCompanyCarbonFootprintService;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\Reopen\ReopenEventCarbonFootprintService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Reopen\ReopenPropertyCarbonFootprintService;
use App\CarbonFootprint\Domain\Event\CarbonFootprintReport\ReopenCarbonFootprintOnCarbonFootprintReportRejectedSubscriber;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRejected;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTextsDrafted;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\TestCase;

class ReopenCarbonFootprintOnCarbonFootprintReportRejectedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToRejected(): void
    {
        [$sut] = $this->createSUT();

        $this->assertTrue($sut->isSubscribedTo(new CarbonFootprintReportRejected(CarbonFootprintReportMother::random()->id(), UserId::generate(), 'nope')));
        $this->assertFalse($sut->isSubscribedTo(new CarbonFootprintReportTextsDrafted(CarbonFootprintReportMother::random()->id(), [])));
    }

    public function testReopensThePropertyFootprintOfTheRejectedReport(): void
    {
        [$sut, $reports, , $properties] = $this->createSUT();

        $footprint = PropertyCarbonFootprintMother::withProvidersAnswered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id');
        $footprint->complete(new Emissions());
        $properties->add($footprint);

        $report = CarbonFootprintReportMother::pendingReview(CarbonFootprintReportSubjectType::Property, (string) $footprint->id());
        $reports->add($report);

        $sut->handle(new CarbonFootprintReportRejected($report->id(), UserId::generate(), 'Revisa'));

        $this->assertFalse($footprint->isCompleted());
    }

    public function testReopensTheCompanyFootprintOfTheRejectedReport(): void
    {
        [$sut, $reports, , , $companies] = $this->createSUT();

        $footprint = CompanyCarbonFootprintMother::withProvidersAnswered((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), 'account-id');
        $footprint->complete(new Emissions());
        $companies->add($footprint);

        $report = CarbonFootprintReportMother::pendingReview(CarbonFootprintReportSubjectType::Company, (string) $footprint->id());
        $reports->add($report);

        $sut->handle(new CarbonFootprintReportRejected($report->id(), UserId::generate(), 'Revisa'));

        $this->assertFalse($footprint->isCompleted());
    }

    public function testReopensTheEventFootprintOfTheRejectedReport(): void
    {
        [$sut, $reports, $events] = $this->createSUT();

        $footprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $footprint->complete(new Emissions(), new Emissions());
        $events->add($footprint);

        $report = CarbonFootprintReportMother::pendingReview(CarbonFootprintReportSubjectType::Event, (string) $footprint->id());
        $reports->add($report);

        $sut->handle(new CarbonFootprintReportRejected($report->id(), UserId::generate(), 'Revisa'));

        $this->assertFalse($footprint->isCompleted());
    }

    /** @return array{0: ReopenCarbonFootprintOnCarbonFootprintReportRejectedSubscriber, 1: \App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCarbonFootprintReportRepository, 2: \App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository, 3: \App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository, 4: \App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository} */
    private function createSUT(): array
    {
        $reports = CarbonFootprintReportRepositoryMother::inMemory();
        $events = EventCarbonFootprintRepositoryMother::inMemory();
        $properties = PropertyCarbonFootprintRepositoryMother::inMemory();
        $companies = CompanyCarbonFootprintRepositoryMother::inMemory();

        $subjects = new CarbonFootprintReportSubjects(
            $events,
            $properties,
            $companies,
            new ReopenEventCarbonFootprintService($events, new EventCarbonFootprintAuthorizer()),
            new ReopenPropertyCarbonFootprintService($properties, new PropertyCarbonFootprintAuthorizer()),
            new ReopenCompanyCarbonFootprintService($companies, new CompanyCarbonFootprintAuthorizer()),
        );

        return [new ReopenCarbonFootprintOnCarbonFootprintReportRejectedSubscriber($reports, $subjects, new FakeLoggedUser(UserMother::adminUser())), $reports, $events, $properties, $companies];
    }
}
