<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Answer;

use App\CarbonFootprint\Application\Activity\Answer\ActivityAnswerHandler;
use App\CarbonFootprint\Application\Activity\Answer\ActivityAnswerHandlerRegistry;
use App\CarbonFootprint\Application\Activity\Answer\PhaseAnswersSynchronizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintCountryResolver;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\MachineryFuelAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\MaterialAnswer;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PhaseAnswersSynchronizerTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testRegistersNewAnswersAndAddsTheResultingActivityToThePhase(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $registeredActivityId = ActivityId::generate();

        $handler = $this->materialHandler();
        $handler->expects($this->once())
            ->method('register')
            ->with(
                $this->isInstanceOf(MaterialAnswer::class),
                $eventCarbonFootprint->calculationYear(),
                $eventCarbonFootprint->emissionFactorGroupId(),
                $eventCarbonFootprint->accountId(),
                $this->isInstanceOf(User::class),
            )
            ->willReturn($registeredActivityId);
        $handler->expects($this->never())->method('update');

        $sut = $this->createSUT($handler);

        $sut->sync($eventCarbonFootprint, Phase::Materials, [$this->materialsSection([$this->materialAnswer()])], UserMother::ownerUser());

        $activityIds = $eventCarbonFootprint->activities()->activityIds();
        $this->assertCount(1, $activityIds);
        $this->assertTrue($activityIds[0]->equals($registeredActivityId));
    }

    public function testUpdatesAnswersThatMatchAnExistingActivityWithoutAddingDuplicates(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $existingActivityId = ActivityId::generate();
        $eventCarbonFootprint->addActivity($existingActivityId, Phase::Materials, Source::Materials);

        $handler = $this->materialHandler();
        $handler->expects($this->once())
            ->method('update')
            ->with($this->isInstanceOf(MaterialAnswer::class), $eventCarbonFootprint->calculationYear(), $eventCarbonFootprint->emissionFactorGroupId(), $this->isInstanceOf(User::class));
        $handler->expects($this->never())->method('register');

        $sut = $this->createSUT($handler);

        $sut->sync($eventCarbonFootprint, Phase::Materials, [$this->materialsSection([$this->materialAnswer($existingActivityId)])], UserMother::ownerUser());

        $activityIds = $eventCarbonFootprint->activities()->activityIds();
        $this->assertCount(1, $activityIds);
        $this->assertTrue($activityIds[0]->equals($existingActivityId));
    }

    public function testAPhaseWithoutAnswersForASourceRemovesEverythingFromThatSource(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $staleActivityId = ActivityId::generate();
        $eventCarbonFootprint->addActivity($staleActivityId, Phase::Materials, Source::Materials);

        $sut = $this->createSUT();

        $sut->sync($eventCarbonFootprint, Phase::Materials, [], UserMother::ownerUser());

        $this->assertCount(0, $eventCarbonFootprint->activities()->activityIds());
    }

    public function testDoesNotTouchActivitiesFromOtherPhases(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $otherPhaseActivityId = ActivityId::generate();
        $eventCarbonFootprint->addActivity($otherPhaseActivityId, Phase::Assembly, Source::MachineryOperation);

        $sut = $this->createSUT();

        $sut->sync($eventCarbonFootprint, Phase::Materials, [], UserMother::ownerUser());

        $activityIds = $eventCarbonFootprint->activities()->activityIds();
        $this->assertCount(1, $activityIds);
        $this->assertTrue($activityIds[0]->equals($otherPhaseActivityId));
    }

    public function testTwoSectionsSharingTheSameSourceAreReconciledTogetherWithoutErasingEachOther(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();

        $materialHandler = $this->materialHandler();
        $materialActivityId = ActivityId::generate();
        $materialHandler->method('register')->willReturn($materialActivityId);

        $machineryFuelHandler = $this->createMock(ActivityAnswerHandler::class);
        $machineryFuelHandler->method('activityType')->willReturn(ActivityType::MachineryFuel);
        $machineryFuelActivityId = ActivityId::generate();
        $machineryFuelHandler->method('register')->willReturn($machineryFuelActivityId);

        $handlers = new ActivityAnswerHandlerRegistry([$machineryFuelHandler]);
        $sut = new PhaseAnswersSynchronizer($handlers, $this->countryResolver());

        $machineryFuelSection = new SectionAnswer(
            Phase::Assembly->sections()[2],
            [new MachineryFuelAnswer(new MachineryFuelId('mf-1'), new Quantity(10.0, Unit::Liter))],
        );

        $sut->sync($eventCarbonFootprint, Phase::Assembly, [$machineryFuelSection], UserMother::ownerUser());

        $activityIds = $eventCarbonFootprint->activities()->filterBySource(Source::MachineryOperation)->activityIds();
        $this->assertCount(1, $activityIds);
        $this->assertTrue($activityIds[0]->equals($machineryFuelActivityId));
    }

    public function testManualSyncDoesNotEraseEmployeeActivitiesOnTheSamePhaseAndSource(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $employeeActivityId = ActivityId::generate();
        $eventCarbonFootprint->addActivity($employeeActivityId, Phase::Materials, Source::Materials, null, true);

        $sut = $this->createSUT();

        $sut->sync($eventCarbonFootprint, Phase::Materials, [], UserMother::ownerUser());

        $activityIds = $eventCarbonFootprint->activities()->activityIds();
        $this->assertCount(1, $activityIds);
        $this->assertTrue($activityIds[0]->equals($employeeActivityId));
    }

    public function testEmployeeSyncDoesNotEraseManualActivitiesOnTheSamePhaseAndSource(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $manualActivityId = ActivityId::generate();
        $eventCarbonFootprint->addActivity($manualActivityId, Phase::Materials, Source::Materials);

        $sut = $this->createSUT();

        $sut->sync($eventCarbonFootprint, Phase::Materials, [], UserMother::ownerUser(), null, null, true);

        $activityIds = $eventCarbonFootprint->activities()->activityIds();
        $this->assertCount(1, $activityIds);
        $this->assertTrue($activityIds[0]->equals($manualActivityId));
    }

    public function testThrowsWhenEventCarbonFootprintHasNoEmissionFactorGroupConfigured(): void
    {
        $eventCarbonFootprint = new EventCarbonFootprint(id: EventCarbonFootprintId::generate());

        $sut = $this->createSUT();

        $this->expectException(\InvalidArgumentException::class);
        $sut->sync($eventCarbonFootprint, Phase::Materials, [$this->materialsSection([$this->materialAnswer()])], UserMother::ownerUser());
    }

    /** @param list<MaterialAnswer> $answers */
    private function materialsSection(array $answers): SectionAnswer
    {
        return new SectionAnswer(Phase::Materials->sections()[0], $answers);
    }

    private function materialAnswer(?ActivityId $activityId = null): MaterialAnswer
    {
        return new MaterialAnswer(
            materialId: new MaterialId('material-id'),
            condition: ItemCondition::New,
            quantity: new Quantity(10.0, Unit::Kilogram),
            existingActivityId: $activityId,
        );
    }

    public function testPassesTheCountryOfTheEventPropertyToTheHandler(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::from(eventId: 'E-1', id: (string) EventCarbonFootprintId::generate());

        $eventRepository = EventRepositoryMother::inMemory();
        $eventRepository->add(EventMother::from(name: 'Event', id: 'E-1', propertyId: 'P-1'));

        $propertyRepository = PropertyRepositoryMother::inMemory();
        $propertyRepository->add(PropertyMother::from(
            propertyId: new PropertyId('P-1'),
            info: new PropertyInfo(countryId: new CountryId('C-ES')),
        ));

        $handler = $this->materialHandler();
        $handler->expects($this->once())
            ->method('register')
            ->with(
                $this->isInstanceOf(MaterialAnswer::class),
                $eventCarbonFootprint->calculationYear(),
                $eventCarbonFootprint->emissionFactorGroupId(),
                $eventCarbonFootprint->accountId(),
                $this->isInstanceOf(User::class),
                $this->equalTo(new CountryId('C-ES')),
            )
            ->willReturn(ActivityId::generate());

        $sut = $this->createSUT($handler, new EventCarbonFootprintCountryResolver($eventRepository, $propertyRepository));

        $sut->sync($eventCarbonFootprint, Phase::Materials, [$this->materialsSection([$this->materialAnswer()])], UserMother::ownerUser());
    }

    private function materialHandler(): ActivityAnswerHandler&MockObject
    {
        $handler = $this->createMock(ActivityAnswerHandler::class);
        $handler->method('activityType')->willReturn(ActivityType::Material);

        return $handler;
    }

    private function createSUT(?ActivityAnswerHandler $handler = null, ?EventCarbonFootprintCountryResolver $countryResolver = null): PhaseAnswersSynchronizer
    {
        $handlers = null !== $handler ? [$handler] : [];

        return new PhaseAnswersSynchronizer(new ActivityAnswerHandlerRegistry($handlers), $countryResolver ?? $this->countryResolver());
    }

    private function countryResolver(): EventCarbonFootprintCountryResolver
    {
        return new EventCarbonFootprintCountryResolver(EventRepositoryMother::inMemory(), PropertyRepositoryMother::inMemory());
    }
}
