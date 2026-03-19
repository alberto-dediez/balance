<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Domain\Event\DailyWaste;

use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRemoved;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use App\Tests\Unit\Shared\Application\CommandBus\SluttyCommandHandler;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementMother;
use App\Tests\Unit\Waste\Domain\Model\Measurement\MeasurementRepositoryMother;
use App\Waste\Application\DailyWaste\Calculate\CalculateDailyWasteRequest;
use App\Waste\Domain\Event\DailyWaste\MeasurementSubscriber;
use App\Waste\Domain\Model\Measurement\MeasurementRegistered;
use App\Waste\Domain\Model\Measurement\MeasurementRemoved;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Waste\Domain\Model\Measurement\MeasurementUpdated;
use PHPUnit\Framework\TestCase;

class MeasurementSubscriberTest extends TestCase
{
    private SluttyCommandHandler $spyCommandBus;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        CommandBusDispatcher::instance()->reset();

        $this->spyCommandBus = new SluttyCommandHandler();
    }

    public function isSubscribedToReturnsTrueIfMeasurementRemovedEventIsPassed(): void
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
            day: $measurement->day(),
        );

        $result = $sut->isSubscribedTo($event);

        $this->assertTrue($result);
    }

    public function isSubscribedToReturnsTrueIfMeasurementUpdatedEventIsPassed(): void
    {
        $measurement = MeasurementMother::random();

        $repository = MeasurementRepositoryMother::inMemory();
        $repository->add($measurement);

        $sut = $this->createSUT($repository);

        $event = new MeasurementUpdated(
            measurementId: $measurement->id(),
            quantity: $measurement->quantity(),
            load: $measurement->load(),
            justification: $measurement->justification(),
        );

        $result = $sut->isSubscribedTo($event);

        $this->assertTrue($result);
    }

    public function isSubscribedToReturnsTrueIfMeasurementRegisteredEventIsPassed(): void
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
            measurementMethod: $measurement->measurementMethod(),
            purpose: $measurement->purpose(),
            wasteManagerId: $measurement->wasteManagerId(),
            registeredBy: $measurement->registeredBy(),
            accountId: $measurement->accountId(),
            day: $measurement->day(),
            quantity: $measurement->quantity(),
            load: $measurement->load(),
            justification: $measurement->justification(),
        );

        $result = $sut->isSubscribedTo($event);

        $this->assertTrue($result);
    }

    public function isSubscribedToReturnsFalseWithOtherEvents(): void
    {
        $repository = MeasurementRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $event = new PropertyRemoved(
            PropertyId::generate(),
        );

        $result = $sut->isSubscribedTo($event);

        $this->assertFalse($result);
    }

    public function testHandleRemove(): void
    {
        $measurement = MeasurementMother::random();

        $repository = MeasurementRepositoryMother::inMemory();
        $repository->add($measurement);

        $sut = $this->createSUT(
            repository: $repository,
        );

        $event = new MeasurementRemoved(
            measurementId: $measurement->id(),
            propertyId: $measurement->propertyId(),
            wasteId: $measurement->wasteId(),
            purpose: $measurement->purpose(),
            wasteManagerId: $measurement->wasteManagerId(),
            accountId: $measurement->accountId(),
            day: $measurement->day(),
        );

        $sut->handle($event);

        $commands = $this->spyCommandBus->commands();

        $this->assertCount(1, $commands);
        $this->assertTrue($commands[0]['command'] instanceof DeferredCommand);
        $this->assertTrue($commands[0]['command']->command() instanceof CalculateDailyWasteRequest);
    }

    public function testHandleUpdate(): void
    {
        $measurement = MeasurementMother::random();

        $repository = MeasurementRepositoryMother::inMemory();
        $repository->add($measurement);

        $sut = $this->createSUT(
            repository: $repository,
        );

        $event = new MeasurementUpdated(
            measurementId: $measurement->id(),
            quantity: $measurement->quantity(),
            load: $measurement->load(),
            justification: $measurement->justification(),
        );

        $sut->handle($event);

        $commands = $this->spyCommandBus->commands();

        $this->assertCount(1, $commands);
        $this->assertTrue($commands[0]['command'] instanceof DeferredCommand);
        $this->assertTrue($commands[0]['command']->command() instanceof CalculateDailyWasteRequest);
    }

    public function testHandleRegister(): void
    {
        $measurement = MeasurementMother::random();

        $repository = MeasurementRepositoryMother::inMemory();
        $repository->add($measurement);

        $sut = $this->createSUT(
            repository: $repository,
        );

        $event = new MeasurementRegistered(
            measurementId: $measurement->id(),
            containerId: $measurement->containerId(),
            propertyId: $measurement->propertyId(),
            wasteId: $measurement->wasteId(),
            measurementMethod: $measurement->measurementMethod(),
            purpose: $measurement->purpose(),
            wasteManagerId: $measurement->wasteManagerId(),
            registeredBy: $measurement->registeredBy(),
            accountId: $measurement->accountId(),
            day: $measurement->day(),
            quantity: $measurement->quantity(),
            load: $measurement->load(),
            justification: $measurement->justification(),
        );

        $sut->handle($event);

        $commands = $this->spyCommandBus->commands();

        $this->assertCount(1, $commands);
        $this->assertTrue($commands[0]['command'] instanceof DeferredCommand);
        $this->assertTrue($commands[0]['command']->command() instanceof CalculateDailyWasteRequest);
    }

    private function createSUT(MeasurementRepository $repository): MeasurementSubscriber
    {
        $loggedUser = new FakeLoggedUser(UserMother::random());

        return new MeasurementSubscriber(
            $repository,
            $loggedUser,
        );
    }
}
