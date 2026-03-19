<?php

declare(strict_types=1);

namespace App\Waste\Domain\Event\DailyWaste;

use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use App\Waste\Application\DailyWaste\Calculate\CalculateDailyWasteRequest;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementRegistered;
use App\Waste\Domain\Model\Measurement\MeasurementRemoved;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;
use App\Waste\Domain\Model\Measurement\MeasurementUpdated;

class MeasurementSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private MeasurementRepository $measurementRepository, private LoggedUser $loggedUser)
    {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        try {
            switch ($event::class) {
                case MeasurementRegistered::class:
                    $this->handleRegister($event);
                    break;
                case MeasurementRemoved::class:
                    $this->handleRemoved($event);
                    break;
                case MeasurementUpdated::class:
                    $this->handleUpdated($event);
                    break;
                default:
                    throw new \RuntimeException('No handler for event!');
            }
        } catch (\InvalidArgumentException) {

        }
    }

    private function handleRegister(MeasurementRegistered $event): void
    {
        CommandBusDispatcher::instance()->dispatch(
            new DeferredCommand(
                command: new CalculateDailyWasteRequest(
                    propertyId: $event->propertyId(),
                    wasteId: $event->wasteId(),
                    wasteManagerId: $event->wasteManagerId(),
                    accountId: $event->accountId(),
                    purpose: $event->purpose(),
                    day: $event->day(),
                ),
            ),
            $this->loggedUser->__invoke(),
        );
    }

    private function handleRemoved(MeasurementRemoved $event): void
    {
        CommandBusDispatcher::instance()->dispatch(
            new DeferredCommand(
                command: new CalculateDailyWasteRequest(
                    propertyId: $event->propertyId(),
                    wasteId: $event->wasteId(),
                    wasteManagerId: $event->wasteManagerId(),
                    accountId: $event->accountId(),
                    purpose: $event->purpose(),
                    day: $event->day(),
                ),
            ),
            $this->loggedUser->__invoke(),
        );
    }

    private function handleUpdated(MeasurementUpdated $event): void
    {
        $measurement = $this->measurementRepository->find($event->measurementId());

        if (!$measurement instanceof Measurement) {
            throw new \InvalidArgumentException('No measurement found!');
        }

        CommandBusDispatcher::instance()->dispatch(
            new DeferredCommand(
                command: new CalculateDailyWasteRequest(
                    propertyId: $measurement->propertyId(),
                    wasteId: $measurement->wasteId(),
                    wasteManagerId: $measurement->wasteManagerId(),
                    accountId: $measurement->accountId(),
                    purpose: $measurement->purpose(),
                    day: $measurement->day(),
                ),
            ),
            $this->loggedUser->__invoke(),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof MeasurementRegistered
            || $event instanceof MeasurementUpdated
            || $event instanceof MeasurementRemoved
        ;
    }
}
