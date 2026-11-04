<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final class FailingOnceDomainEventSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    private bool $failed = false;

    /** @var DomainEvent[] */
    private array $events = [];

    public function handle(DomainEvent $event): void
    {
        $this->events[] = $event;

        if (!$this->failed) {
            $this->failed = true;

            throw new \RuntimeException('Subscriber failed');
        }
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return true;
    }

    /** @return DomainEvent[] */
    public function events(): array
    {
        return $this->events;
    }
}
