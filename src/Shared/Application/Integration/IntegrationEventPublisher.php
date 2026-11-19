<?php

declare(strict_types=1);

namespace App\Shared\Application\Integration;

interface IntegrationEventPublisher
{
    public function publish(IntegrationEvent $event): void;
}
