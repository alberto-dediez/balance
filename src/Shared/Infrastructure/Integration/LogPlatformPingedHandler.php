<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Integration;

use App\Security\Domain\Model\User\User;
use App\Shared\Application\Integration\IntegrationEventHandler;
use App\Shared\Application\Integration\Ping\PlatformPinged;
use App\Shared\Application\Integration\ReceivedIntegrationEvent;
use Psr\Log\LoggerInterface;

class LogPlatformPingedHandler implements IntegrationEventHandler
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function supports(string $type, int $version): bool
    {
        return PlatformPinged::type() === $type && PlatformPinged::version() === $version;
    }

    public function handle(ReceivedIntegrationEvent $event, User $user): void
    {
        $this->logger->info('Platform pinged', [
            'source' => $event->source,
            'id' => $event->id,
            'correlationId' => $event->correlationId,
            'message' => $event->string('message'),
        ]);
    }
}
