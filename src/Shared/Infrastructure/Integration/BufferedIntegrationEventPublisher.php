<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Integration;

use App\Shared\Application\Integration\IntegrationEvent;
use App\Shared\Application\Integration\IntegrationEventPublisher;
use App\Shared\Application\Integration\ReceivedIntegrationEvent;
use App\Shared\Domain\Model\Integration\IntegrationOutboxMessage;
use App\Shared\Domain\Model\Integration\IntegrationOutboxRepository;
use App\Shared\Domain\Uuid\UuidGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\ResetInterface;

class BufferedIntegrationEventPublisher implements IntegrationEventPublisher, ResetInterface
{
    /** @var list<IntegrationOutboxMessage> */
    private array $buffer = [];

    private bool $failed = false;

    public function __construct(
        private readonly IntegrationSettings $settings,
        private readonly IntegrationContext $context,
        private readonly IntegrationOutboxRepository $outbox,
        private readonly LoggerInterface $logger,
    ) {}

    public function publish(IntegrationEvent $event): void
    {
        if (!$this->settings->enabled) {
            return;
        }

        $messageId = UuidGenerator::v7();
        $current = $this->context->current();
        $correlationId = $current instanceof ReceivedIntegrationEvent ? $current->correlationId : $messageId;
        $hops = $current instanceof ReceivedIntegrationEvent ? $current->hops + 1 : 0;

        if ($hops > $this->settings->maxHops) {
            $this->logger->warning('Integration event not published: too many hops', [
                'type' => $event::type(),
                'correlationId' => $correlationId,
                'hops' => $hops,
            ]);

            return;
        }

        $this->buffer[] = new IntegrationOutboxMessage(
            sequence: null,
            messageId: $messageId,
            type: $event::type(),
            version: $event::version(),
            aggregateId: $event->aggregateId(),
            accountRef: $event->accountRef(),
            correlationId: $correlationId,
            causationId: $current?->id,
            hops: $hops,
            occurredOn: new \DateTimeImmutable('NOW'),
            payload: json_encode($event->payload(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
    }

    public function flush(): void
    {
        if ($this->failed) {
            $this->discard();

            return;
        }

        $messages = $this->buffer;
        $this->buffer = [];

        foreach ($messages as $message) {
            $this->outbox->add($message);
        }
    }

    public function markFailed(): void
    {
        $this->failed = true;
    }

    public function discard(): void
    {
        $this->buffer = [];
        $this->failed = false;
    }

    public function reset(): void
    {
        $this->discard();
    }
}
