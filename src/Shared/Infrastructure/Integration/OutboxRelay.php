<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Integration;

use App\Shared\Domain\Model\Integration\IntegrationOutboxRepository;
use App\Shared\Infrastructure\Integration\Messenger\OutgoingIntegrationMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;

class OutboxRelay
{
    public function __construct(
        private readonly IntegrationOutboxRepository $outbox,
        private readonly MessageBusInterface $bus,
        private readonly IntegrationSettings $settings,
        private readonly LoggerInterface $logger,
    ) {}

    public function relay(int $batchSize): int
    {
        $sent = 0;

        foreach ($this->outbox->pending($batchSize) as $message) {
            try {
                $this->bus->dispatch(
                    new OutgoingIntegrationMessage(IntegrationEnvelope::fromOutbox($message, $this->settings->platform)),
                    [new AmqpStamp($message->routingKey(), AMQP_NOPARAM, ['content_type' => 'application/json', 'delivery_mode' => 2])],
                );
            } catch (\Throwable $exception) {
                $message->markFailedAttempt($exception->getMessage());
                $this->outbox->save($message);

                $this->logger->error('Integration outbox message could not be published', [
                    'sequence' => $message->sequence(),
                    'messageId' => $message->messageId(),
                    'type' => $message->type(),
                    'attempts' => $message->attempts(),
                    'exception' => $exception,
                ]);

                return $sent;
            }

            $message->markSent(new \DateTimeImmutable('NOW'));
            $this->outbox->save($message);
            ++$sent;
        }

        return $sent;
    }
}
