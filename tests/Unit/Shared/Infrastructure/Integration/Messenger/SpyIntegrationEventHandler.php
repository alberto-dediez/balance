<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Integration\Messenger;

use App\Security\Domain\Model\User\User;
use App\Shared\Application\Integration\IntegrationEventHandler;
use App\Shared\Application\Integration\ReceivedIntegrationEvent;
use App\Shared\Infrastructure\Integration\IntegrationContext;

class SpyIntegrationEventHandler implements IntegrationEventHandler
{
    /** @var list<array{event: ReceivedIntegrationEvent, user: User, context: ?ReceivedIntegrationEvent}> */
    private array $calls = [];

    /** @param (\Closure(ReceivedIntegrationEvent): void)|null $onHandle */
    public function __construct(
        private readonly IntegrationContext $context,
        private readonly string $type = 'platform.pinged',
        private readonly int $version = 1,
        private readonly ?\Closure $onHandle = null,
    ) {}

    public function supports(string $type, int $version): bool
    {
        return $this->type === $type && $this->version === $version;
    }

    public function handle(ReceivedIntegrationEvent $event, User $user): void
    {
        $this->calls[] = ['event' => $event, 'user' => $user, 'context' => $this->context->current()];

        if (null !== $this->onHandle) {
            ($this->onHandle)($event);
        }
    }

    /** @return list<array{event: ReceivedIntegrationEvent, user: User, context: ?ReceivedIntegrationEvent}> */
    public function calls(): array
    {
        return $this->calls;
    }
}
