<?php

namespace App\Tests\Unit\Waste\Domain\Event\DailyWaste;

use PHPUnit\Framework\TestCase;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Property\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Property\Domain\Model\Property\PropertyRemoved;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Waste\Domain\Model\Measurement\MeasurementRemoved;
use App\Waste\Domain\Model\Measurement\MeasurementUpdated;
use App\Waste\Domain\Event\DailyWaste\MeasurementSubscriber;
use App\Waste\Domain\Model\Measurement\MeasurementRegistered;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Waste\Domain\Event\Measurement\RemovePropertySubscriber;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementMother;
use App\Waste\Application\Measurement\Remove\RemoveMeasurementService;
use App\Waste\Application\DailyWaste\Calculate\CalculateDailyWasteService;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryMother;

class MeasurementSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function isSubscribedToReturnsTrueIfMeasurementRemovedEventIsPassed()
    {
        $measurement = MeasurementMother::random();

        $repository = MeasurementRepositoryMother::inMemory();
        $repository->add($measurement);

        $sut = $this->createSUT($repository);

        $event = new MeasurementRemoved(
            measurementId: $measurement->id(),
            propertyId: $measurement->propertyId(),
            wasteId: $measurement->wasteId(),
            purpose: $measurement->purpose(),
            wasteManagerId: $measurement->wasteManagerId(),
            accountId: $measurement->accountId(),
            day: $measurement->day()
        );

        $result = $sut->isSubscribedTo($event);

        $this->assertTrue($result);
    }

    public function isSubscribedToReturnsTrueIfMeasurementUpdatedEventIsPassed()
    {
        $measurement = MeasurementMother::random();

        $repository = MeasurementRepositoryMother::inMemory();
        $repository->add($measurement);

        $sut = $this->createSUT($repository);

        $event = new MeasurementUpdated(
            measurementId: $measurement->id(),
            quantity: $measurement->quantity(),
            load: $measurement->load(),
            justification: $measurement->justification()
        );

        $result = $sut->isSubscribedTo($event);

        $this->assertTrue($result);
    }

    public function isSubscribedToReturnsTrueIfMeasurementRegisteredEventIsPassed()
    {
        $measurement = MeasurementMother::random();

        $repository = MeasurementRepositoryMother::inMemory();
        $repository->add($measurement);

        $sut = $this->createSUT($repository);

        $event = new MeasurementRegistered(
            measurementId: $measurement->id(),
            containerId: $measurement->containerId(),
            propertyId: $measurement->propertyId(),
            wasteId: $measurement->wasteId(),
            purpose: $measurement->purpose(),
            measurementMethod: $measurement->measurementMethod(),
            wasteManagerId: $measurement->wasteManagerId(),
            quantity: $measurement->quantity(),
            load: $measurement->load(),
            justification: $measurement->justification(),
            accountId: $measurement->accountId(),
            registeredBy: $measurement->registeredBy(),
            day: $measurement->day()
        );

        $result = $sut->isSubscribedTo($event);

        $this->assertTrue($result);
    }

    public function isSubscribedToReturnsFalseWithOtherEvents()
    {
        $repository = MeasurementRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $event = new PropertyRemoved(
            PropertyId::generate()
        );

        $result = $sut->isSubscribedTo($event);

        $this->assertFalse($result);
    }

    public function testHandleRemove()
    {
        $measurement = MeasurementMother::random();

        $repository = MeasurementRepositoryMother::inMemory();
        $repository->add($measurement);

        $sut = $this->createSUT(
            repository: $repository, 
            calculateCalls: 1
        );

        $event = new MeasurementRemoved(
            measurementId: $measurement->id(),
            propertyId: $measurement->propertyId(),
            wasteId: $measurement->wasteId(),
            purpose: $measurement->purpose(),
            wasteManagerId: $measurement->wasteManagerId(),
            accountId: $measurement->accountId(),
            day: $measurement->day()
        );

        $sut->handle($event);

        $this->assertTrue(true);
    }

    public function testHandleUpdate()
    {
        $measurement = MeasurementMother::random();

        $repository = MeasurementRepositoryMother::inMemory();
        $repository->add($measurement);

        $sut = $this->createSUT(
            repository: $repository, 
            calculateCalls: 1
        );

        $event = new MeasurementUpdated(
            measurementId: $measurement->id(),
            quantity: $measurement->quantity(),
            load: $measurement->load(),
            justification: $measurement->justification()
        );

        $sut->handle($event);

        $this->assertTrue(true);
    }

    public function testHandleRegister()
    {
        $measurement = MeasurementMother::random();

        $repository = MeasurementRepositoryMother::inMemory();
        $repository->add($measurement);

        $sut = $this->createSUT(
            repository: $repository, 
            calculateCalls: 1
        );

        $event = new MeasurementRegistered(
            measurementId: $measurement->id(),
            containerId: $measurement->containerId(),
            propertyId: $measurement->propertyId(),
            wasteId: $measurement->wasteId(),
            purpose: $measurement->purpose(),
            measurementMethod: $measurement->measurementMethod(),
            wasteManagerId: $measurement->wasteManagerId(),
            quantity: $measurement->quantity(),
            load: $measurement->load(),
            justification: $measurement->justification(),
            accountId: $measurement->accountId(),
            registeredBy: $measurement->registeredBy(),
            day: $measurement->day()
        );

        $sut->handle($event);

        $this->assertTrue(true);
    }

    private function createSUT(MeasurementRepository $repository, int $calculateCalls = 0)
    {
        $calculateService = $this->createMock(CalculateDailyWasteService::class);
        $calculateService->expects($this->exactly($calculateCalls))->method('__invoke');
        
        /** @var CalculateDailyWasteService $calculateService **/

        $loggedUser = new FakeLoggedUser(UserMother::random());

        return new MeasurementSubscriber(
            $repository,
            $calculateService,
            $loggedUser
        );
    }
}