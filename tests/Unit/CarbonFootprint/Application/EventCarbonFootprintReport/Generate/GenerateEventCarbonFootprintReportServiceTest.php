<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprintReport\Generate;

use App\CarbonFootprint\Application\Activity\Catalog\ActivityCatalogProviderRegistry;
use App\CarbonFootprint\Application\Activity\Catalog\MaterialCatalogProvider;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetResults\GetResultsService;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\Generate\ActivityItemNameResolver;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\Generate\GenerateEventCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\Generate\GenerateEventCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportStatus;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintReportRepository;
use App\CarbonFootprint\Infrastructure\Storage\InMemoryEventCarbonFootprintReportStorage;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\MaterialActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialRepositoryMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;

class GenerateEventCarbonFootprintReportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testGeneratesTwoLocalesAndMarksReportGenerated(): void
    {
        [$sut, $reportRepository, $storage, , $report] = $this->createSUT();

        ($sut)(new GenerateEventCarbonFootprintReportRequest($report->id()), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));

        $persisted = $reportRepository->find($report->id());
        $this->assertNotNull($persisted);
        $this->assertSame(EventCarbonFootprintReportStatus::Generated, $persisted->status());
        $this->assertNotNull($persisted->filePathFor(Locale::Spanish));
        $this->assertNotNull($persisted->filePathFor(Locale::English));
        $this->assertCount(2, $storage->paths());
    }

    public function testGeneratesTextsOnceAndTranslatesThemForTheOtherLocales(): void
    {
        [$sut, , , $textsGenerator, $report] = $this->createSUT();

        ($sut)(new GenerateEventCarbonFootprintReportRequest($report->id()), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));

        $this->assertSame([Locale::Spanish], $textsGenerator->generateCalls());
        $this->assertSame([Locale::English], $textsGenerator->translateCalls());
    }

    public function testFailureMarksReportFailedAndCleansUpStorage(): void
    {
        [$sut, $reportRepository, $storage, $textsGenerator, $report] = $this->createSUT();

        $textsGenerator->failOnGenerate();

        $this->expectException(\RuntimeException::class);

        try {
            ($sut)(new GenerateEventCarbonFootprintReportRequest($report->id()), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));
        } finally {
            $persisted = $reportRepository->find($report->id());
            $this->assertNotNull($persisted);
            $this->assertSame(EventCarbonFootprintReportStatus::Failed, $persisted->status());
            $this->assertSame('Gemini failure', $persisted->errorMessage());
            $this->assertCount(0, $storage->paths());
        }
    }

    public function testReportDataContainsTheExclusionLines(): void
    {
        [$sut, , , $textsGenerator, $report, $eventCarbonFootprint] = $this->createSUT();

        $eventCarbonFootprint->excludeSource(Phase::Assembly, Source::WasteManagement, ExclusionReason::NoDataAvailable, 'no invoices');

        ($sut)(new GenerateEventCarbonFootprintReportRequest($report->id()), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));

        $data = $textsGenerator->lastData();
        $this->assertNotNull($data);
        $this->assertCount(1, $data->exclusionLines);
        $this->assertSame(Phase::Assembly, $data->exclusionLines[0]->phase);
        $this->assertSame(Source::WasteManagement, $data->exclusionLines[0]->source);
        $this->assertSame(ExclusionReason::NoDataAvailable, $data->exclusionLines[0]->reason);
        $this->assertSame('no invoices', $data->exclusionLines[0]->comment);
    }

    public function testIsIdempotentWhenAlreadyGenerated(): void
    {
        [$sut, $reportRepository, , $textsGenerator, $report] = $this->createSUT();

        ($sut)(new GenerateEventCarbonFootprintReportRequest($report->id()), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));
        ($sut)(new GenerateEventCarbonFootprintReportRequest($report->id()), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));

        $this->assertCount(1, $textsGenerator->generateCalls());
        $this->assertCount(1, $textsGenerator->translateCalls());
    }

    /**
     * @return array{0: GenerateEventCarbonFootprintReportService, 1: InMemoryEventCarbonFootprintReportRepository, 2: InMemoryEventCarbonFootprintReportStorage, 3: FakeReportTextsGenerator, 4: EventCarbonFootprintReport, 5: EventCarbonFootprint}
     */
    private function createSUT(): array
    {
        $eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();
        $reportRepository = EventCarbonFootprintReportRepositoryMother::inMemory();
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
        $activity->update($activity->materialId(), $activity->condition(), ActivityMother::emissionFactor($emissionFactorGroup->id()), ActivityMother::emissions(), $activity->quantity());
        $activityRepository->save($activity);

        $eventCarbonFootprint = EventCarbonFootprintMother::from(
            eventId: 'event-1',
            id: (string) EventCarbonFootprintId::generate(),
            accountId: 'account-id',
            emissionFactorGroupId: $emissionFactorGroup->id(),
        );
        $eventCarbonFootprint->addActivity($activity->id(), Phase::Materials, Source::Materials);
        $eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $report = EventCarbonFootprintReportMother::pending((string) $eventCarbonFootprint->id(), 'account-id');
        $reportRepository->add($report);

        $getResultsService = new GetResultsService(
            $eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
            $activityRepository,
        );

        $itemNameResolver = new ActivityItemNameResolver(
            new ActivityCatalogProviderRegistry([new MaterialCatalogProvider($materialRepository)]),
            new FakeTranslator(),
        );

        $textsGenerator = new FakeReportTextsGenerator();
        $storage = new InMemoryEventCarbonFootprintReportStorage();

        $sut = new GenerateEventCarbonFootprintReportService(
            $reportRepository,
            $eventCarbonFootprintRepository,
            $eventRepository,
            $emissionFactorGroupRepository,
            $activityRepository,
            $getResultsService,
            $itemNameResolver,
            $textsGenerator,
            new FakeReportChartsGenerator(),
            new FakeReportPdfGenerator(),
            $storage,
            'es|en',
        );

        return [$sut, $reportRepository, $storage, $textsGenerator, $report, $eventCarbonFootprint];
    }
}
