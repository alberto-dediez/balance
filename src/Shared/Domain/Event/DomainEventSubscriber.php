<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

interface DomainEventSubscriber
{
    public function handle(DomainEvent $event): void;

    public function isSubscribedTo(DomainEvent $event): bool;

    public function register(): void;
}
