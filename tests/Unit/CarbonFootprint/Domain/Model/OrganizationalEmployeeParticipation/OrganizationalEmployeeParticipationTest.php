<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationAggregated;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationRegistered;
use App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationUpdated;
use App\Shared\Domain\Event\DomainEventPublisher;
use PHPUnit\Framework\TestCase;

class OrganizationalEmployeeParticipationTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testRegisterSetsTheFootprintAndPublishesTheEvent(): void
    {
        $participation = OrganizationalEmployeeParticipationMother::from(CarbonFootprintReportSubjectType::Company, 'footprint-1', 'participation-1', 'account-1');

        $registered = array_filter($participation->recordedEvents(), static fn($e) => $e instanceof OrganizationalEmployeeParticipationRegistered);
        $this->assertCount(1, $registered);
        $this->assertSame(CarbonFootprintReportSubjectType::Company, $participation->subjectType());
        $this->assertSame('footprint-1', $participation->carbonFootprintId());
        $this->assertSame('account-1', (string) $participation->accountId());
        $this->assertNull($participation->totalEmployees());
        $this->assertNull($participation->aggregatedAt());
    }

    public function testUpdateSetsTheTotalAndTheConfidenceLevel(): void
    {
        $participation = OrganizationalEmployeeParticipationMother::registered();

        $participation->update(40, ConfidenceLevel::Ninety);

        $this->assertSame(40, $participation->totalEmployees());
        $this->assertSame(ConfidenceLevel::Ninety, $participation->confidenceLevel());
    }

    public function testUpdateWithoutChangesDoesNotPublish(): void
    {
        $participation = OrganizationalEmployeeParticipationMother::registered();
        $participation->update(40, ConfidenceLevel::NinetyFive);
        $participation->clearEvents();

        $participation->update(40, ConfidenceLevel::NinetyFive);

        $updated = array_filter($participation->recordedEvents(), static fn($e) => $e instanceof OrganizationalEmployeeParticipationUpdated);
        $this->assertCount(0, $updated);
    }

    public function testUpdateWithZeroEmployeesThrows(): void
    {
        $participation = OrganizationalEmployeeParticipationMother::registered();

        $this->expectException(\InvalidArgumentException::class);

        $participation->update(0, ConfidenceLevel::NinetyFive);
    }

    public function testRecordAggregationStoresTheResult(): void
    {
        $participation = OrganizationalEmployeeParticipationMother::registered();

        $participation->recordAggregation(8, 0.31);

        $aggregated = array_filter($participation->recordedEvents(), static fn($e) => $e instanceof OrganizationalEmployeeParticipationAggregated);
        $this->assertCount(1, $aggregated);
        $this->assertSame(8, $participation->answeredCount());
        $this->assertSame(0.31, $participation->marginOfError());
        $this->assertNotNull($participation->aggregatedAt());
    }
}
