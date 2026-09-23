<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\Data;

use App\CarbonFootprint\Application\Activity\Catalog\ActivityCatalogProviderRegistry;
use App\CarbonFootprint\Application\Activity\Catalog\MaterialCatalogProvider;
use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\CarbonFootprintReport\Data\ActivityItemNameResolver;
use App\CarbonFootprint\Application\CarbonFootprintReport\Data\EventCarbonFootprintReportDataCollector;
use App\CarbonFootprint\Application\CarbonFootprintReport\Data\EventCompensationCollector;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Application\ContributionCertificate\Generate\FakeContributionCertificateVerificationUrlGenerator;
use App\Tests\Unit\Balance\Domain\Model\ContributionCertificate\ContributionCertificateRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\MaterialActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialRepositoryMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;

class EventCarbonFootprintReportDataCollectorTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testCollectsTheDataOfTheEventWithoutRequiringAnAuthorizer(): void
    {
        [$sut, $eventCarbonFootprint] = $this->createSUT();

        $data = $sut->collect(CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Event, (string) $eventCarbonFootprint->id()));

        $this->assertInstanceOf(EventCarbonFootprintReportData::class, $data);
        $this->assertSame('Event', $data->subjectName());
        $this->assertSame(CarbonFootprintReportSubjectType::Event, $data->subjectType());
        $this->assertSame('30', $data->totalEmissions()->co2eq());
        $this->assertSame('10', $data->emissionsPerAttendee->co2eq());
        $this->assertSame('15', $data->emissionsBySource[Source::Materials->value]->co2eq());
        $this->assertSame('15', $data->emissionsByPhase[Phase::Materials->value]->co2eq());
        $this->assertCount(1, $data->activityLines);
        $this->assertSame('material-name', $data->activityLines[0]->itemName);
        $this->assertNotNull($data->compensation);
        $this->assertFalse($data->compensation->hasCompensations());
    }

    public function testCollectsTheExclusionLines(): void
    {
        [$sut, $eventCarbonFootprint] = $this->createSUT(true);

        $data = $sut->collect(CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Event, (string) $eventCarbonFootprint->id()));

        $this->assertInstanceOf(EventCarbonFootprintReportData::class, $data);
        $this->assertCount(1, $data->exclusionLines);
        $this->assertSame(Phase::Assembly, $data->exclusionLines[0]->phase);
        $this->assertSame(Source::WasteManagement, $data->exclusionLines[0]->source);
        $this->assertSame(ExclusionReason::NoDataAvailable, $data->exclusionLines[0]->reason);
        $this->assertSame('no invoices', $data->exclusionLines[0]->comment);
    }

    public function testThrowsWhenTheFootprintDoesNotExist(): void
    {
        [$sut] = $this->createSUT();

        $this->expectException(\RuntimeException::class);

        $sut->collect(CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Event, (string) EventCarbonFootprintId::generate()));
    }

    /** @return array{0: EventCarbonFootprintReportDataCollector, 1: EventCarbonFootprint} */
    private function createSUT(bool $withExclusion = false): array
    {
        $eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();
        $eventRepository = EventRepositoryMother::inMemory();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $activityRepository = ActivityRepositoryMother::inMemory();
        $materialRepository = MaterialRepositoryMother::inMemory();

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $event = EventMother::fromNameAndAccountAndId('Event', 'account-id', 'event-1');
        $eventRepository->add($event);

        $material = MaterialMother::registeredMaterial();
        $material->setEmissionFactor(new EmissionFactor('5', '5', '0', '0', 2026, $emissionFactorGroup->id(), Unit::Kilogram), new EmissionFactor('5', '5', '0', '0', 2026, $emissionFactorGroup->id(), Unit::Kilogram));
        $materialRepository->add($material);

        $activity = MaterialActivityMother::registered(new AccountId('account-id'));
        $activity->update($material->id(), $activity->condition(), ActivityMother::emissionFactor($emissionFactorGroup->id()), ActivityMother::emissions(), $activity->quantity());
        $activityRepository->save($activity);

        $eventCarbonFootprint = EventCarbonFootprintMother::from(
            eventId: 'event-1',
            id: (string) EventCarbonFootprintId::generate(),
            accountId: 'account-id',
            emissionFactorGroupId: $emissionFactorGroup->id(),
        );
        $eventCarbonFootprint->addActivity($activity->id(), new ActivityPlacement(Phase::Materials, Source::Materials));

        if ($withExclusion) {
            $eventCarbonFootprint->excludeSource(Phase::Assembly, Source::WasteManagement, ExclusionReason::NoDataAvailable, 'no invoices');
        }

        $eventCarbonFootprint->complete(new Emissions('30', '30', '0', '0'), new Emissions('10', '10', '0', '0'));
        $eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $sut = new EventCarbonFootprintReportDataCollector(
            $eventCarbonFootprintRepository,
            $eventRepository,
            $emissionFactorGroupRepository,
            $activityRepository,
            new EmissionsBreakdownBuilder($activityRepository),
            new ActivityItemNameResolver(
                new ActivityCatalogProviderRegistry([new MaterialCatalogProvider($materialRepository)]),
                new FakeTranslator(),
            ),
            new EventCompensationCollector(
                ProjectContributionRepositoryMother::inMemory(),
                ContributionCertificateRepositoryMother::inMemory(),
                new FakeContributionCertificateVerificationUrlGenerator(),
                ProjectRepositoryMother::inMemory(),
                AttendeeSurveyRepositoryMother::inMemory(),
                AttendeeSurveyResponseRepositoryMother::inMemory(),
            ),
        );

        return [$sut, $eventCarbonFootprint];
    }
}
