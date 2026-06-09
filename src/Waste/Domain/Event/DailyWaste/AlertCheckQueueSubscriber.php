<?php

declare(strict_types=1);

namespace App\Waste\Domain\Event\DailyWaste;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use App\Waste\Application\AlertCheckQueue\Register\RegisterAlertCheckQueueRequest;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRegistered;
use App\Waste\Domain\Model\DailyWaste\DailyWasteUpdated;

class AlertCheckQueueSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private readonly LoggedUser $loggedUser)
    {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        match ($event::class) {
            DailyWasteRegistered::class => $this->enqueue($event->accountId(), $event->day()),
            DailyWasteUpdated::class => $this->enqueue($event->accountId(), $event->day()),
            default => throw new \RuntimeException('No handler for event!'),
        };
    }

    private function enqueue(AccountId $accountId, \DateTimeImmutable $checkDate): void
    {
        CommandBusDispatcher::instance()->dispatch(
            new RegisterAlertCheckQueueRequest(
                accountId: $accountId,
                checkDate: $checkDate,
            ),
            $this->loggedUser->__invoke(),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof DailyWasteRegistered
            || $event instanceof DailyWasteUpdated
        ;
    }
}
