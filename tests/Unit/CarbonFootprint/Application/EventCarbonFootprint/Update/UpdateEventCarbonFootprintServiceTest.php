<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprint\Update;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\Update\UpdateEventCarbonFootprintRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\Update\UpdateEventCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintUpdated;
use App\EventOperations\Domain\Model\Event\DateRange;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateEventCarbonFootprintServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = EventCarbonFootprintRepositoryMother::inMemory();

        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount(
            (string) EventId::generate(),
            'account-id',
        );
        $repository->add($eventCarbonFootprint);

        $sut = $this->createSUT($repository);

        $calculationPeriod = new DateRange(
            startDate: new \DateTimeImmutable('2026-02-01'),
            endDate: new \DateTimeImmutable('2026-02-28'),
        );

        $request = new UpdateEventCarbonFootprintRequest(
            eventCarbonFootprintId: $eventCarbonFootprint->id(),
            calculationPeriodStartDate: $calculationPeriod->startDate(),
            calculationPeriodEndDate: $calculationPeriod->endDate(),
            totalEmissions: new Emissions(co2eq: '125.5', co2: '100', ch4: '20', n2o: '5'),
            emissionsPerAttendee: new Emissions(co2eq: '1.5', co2: '1.2', ch4: '0.2', n2o: '0.1'),
            status: EventCarbonFootprintStatus::MaterialsAnswered,
        );

        $sut($request, UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')));

        $eventCarbonFootprintPersisted = $repository->find($eventCarbonFootprint->id());

        $this->assertNotNull($eventCarbonFootprintPersisted);
        $this->assertEquals($calculationPeriod->startDate(), $eventCarbonFootprintPersisted->calculationPeriod()->startDate());
        $this->assertEquals($calculationPeriod->endDate(), $eventCarbonFootprintPersisted->calculationPeriod()->endDate());
        $this->assertEquals('125.5', $eventCarbonFootprintPersisted->totalEmissions()->co2eq());
        $this->assertEquals('100', $eventCarbonFootprintPersisted->totalEmissions()->co2());
        $this->assertEquals('20', $eventCarbonFootprintPersisted->totalEmissions()->ch4());
        $this->assertEquals('5', $eventCarbonFootprintPersisted->totalEmissions()->n2o());
        $this->assertEquals('1.5', $eventCarbonFootprintPersisted->emissionsPerAttendee()->co2eq());
        $this->assertEquals('1.2', $eventCarbonFootprintPersisted->emissionsPerAttendee()->co2());
        $this->assertEquals('0.2', $eventCarbonFootprintPersisted->emissionsPerAttendee()->ch4());
        $this->assertEquals('0.1', $eventCarbonFootprintPersisted->emissionsPerAttendee()->n2o());
        $this->assertEquals(EventCarbonFootprintStatus::MaterialsAnswered, $eventCarbonFootprintPersisted->status());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EventCarbonFootprintUpdated);
        $this->assertTrue($eventCarbonFootprintPersisted->id()->equals($lastEvent->eventCarbonFootprintId()));
    }

    public function testIsHandle(): void
    {
        $repository = EventCarbonFootprintRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $eventCarbonFootprint = EventCarbonFootprintMother::random();
        $repository->add($eventCarbonFootprint);

        $result = $sut->isHandled(new UpdateEventCarbonFootprintRequest(
            eventCarbonFootprintId: $eventCarbonFootprint->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(EventCarbonFootprintRepository $repository): UpdateEventCarbonFootprintService
    {
        return new UpdateEventCarbonFootprintService(
            eventCarbonFootprintRepository: $repository,
            authorizer: new EventCarbonFootprintAuthorizer(),
        );
    }
}
