<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Integration;

use App\Shared\Application\Integration\ReceivedIntegrationEvent;
use Symfony\Contracts\Service\ResetInterface;

class IntegrationContext implements ResetInterface
{
    private ?ReceivedIntegrationEvent $current = null;

    public function enter(ReceivedIntegrationEvent $event): void
    {
        $this->current = $event;
    }

    public function leave(): void
    {
        $this->current = null;
    }

    public function current(): ?ReceivedIntegrationEvent
    {
        return $this->current;
    }

    public function reset(): void
    {
        $this->leave();
    }
}
