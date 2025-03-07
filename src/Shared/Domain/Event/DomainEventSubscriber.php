<?php

namespace App\Shared\Domain\Event;

interface DomainEventSubscriber
{
    public function handle(DomainEvent $event);

    public function isSubscribedTo(DomainEvent $event);

    public function register();
}