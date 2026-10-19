<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Messenger;

use App\Shared\Domain\Model\Health\HealthIndicator;
use App\Shared\Domain\Model\Health\HealthIndicatorResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransport;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;

class QueueHealthIndicator implements HealthIndicator
{
    /** @param array<string, string> $transportNames */
    public function __construct(
        #[Autowire(service: 'messenger.receiver_locator')]
        private readonly ContainerInterface $receiverLocator,
        private readonly Connection $connection,
        private readonly LoggerInterface $logger,
        private readonly array $transportNames,
        private readonly int $maxWaitMinutes,
    ) {}

    public function name(): string
    {
        return 'queue';
    }

    public function check(): HealthIndicatorResult
    {
        foreach ($this->transportNames as $transportName => $queueName) {
            if (!$this->receiverLocator->has($transportName)) {
                continue;
            }

            try {
                $receiver = $this->receiverLocator->get($transportName);

                if ($receiver instanceof MessageCountAwareInterface) {
                    $receiver->getMessageCount();
                }

                if ($receiver instanceof DoctrineTransport && $this->hasMessagesWaitingTooLong($queueName)) {
                    return HealthIndicatorResult::unhealthy($this->name(), 'messages waiting too long');
                }
            } catch (\Throwable $e) {
                $this->logger->error('Queue health check failed', ['transport' => $transportName, 'exception' => $e]);

                return HealthIndicatorResult::unhealthy($this->name(), 'transport unreachable');
            }
        }

        return HealthIndicatorResult::healthy($this->name());
    }

    private function hasMessagesWaitingTooLong(string $queueName): bool
    {
        $now = new \DateTimeImmutable('UTC');

        $oldest = $this->connection->fetchOne(
            'SELECT MIN(available_at) FROM messenger_messages WHERE queue_name = :queue AND delivered_at IS NULL AND available_at <= :now',
            ['queue' => $queueName, 'now' => $now],
            ['queue' => Types::STRING, 'now' => Types::DATETIME_IMMUTABLE],
        );

        if (!is_string($oldest) || '' === $oldest) {
            return false;
        }

        $limit = $now->modify(sprintf('-%d minutes', $this->maxWaitMinutes));

        return new \DateTimeImmutable($oldest, new \DateTimeZone('UTC')) < $limit;
    }
}
