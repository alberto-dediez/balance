<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class VeryPickyDomainEventSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    /** @param DomainEvent[] $events  */
    public function __construct(private array $events = [])
    {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        $this->events[] = $event;
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return false;
    }

    /** @return DomainEvent[] $events  */
    public function events(): array
    {
        return $this->events;
    }
}
