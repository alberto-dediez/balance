<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\InMemory\Repository;

use App\Shared\Domain\Model\Integration\IntegrationInboxFilter;
use App\Shared\Domain\Model\Integration\IntegrationInboxMessage;
use App\Shared\Domain\Model\Integration\IntegrationInboxRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryIntegrationInboxRepository implements IntegrationInboxRepository
{
    /** @var array<string, IntegrationInboxMessage> */
    private array $messages = [];

    public function has(string $messageId): bool
    {
        return null !== $this->find($messageId);
    }

    public function find(string $messageId): ?IntegrationInboxMessage
    {
        return $this->messages[$messageId] ?? null;
    }

    public function add(IntegrationInboxMessage $message): void
    {
        $this->messages[$message->messageId()] = $message;
    }

    public function list(IntegrationInboxFilter $filter): array
    {
        $messages = array_values(array_filter(
            $this->messages,
            static fn(IntegrationInboxMessage $message): bool => (null === $filter->status || $message->status() === $filter->status)
                && (null === $filter->source || '' === $filter->source || $message->source() === $filter->source)
                && (null === $filter->type || '' === $filter->type || $message->type() === $filter->type),
        ));

        $way = 'ASC' === $filter->orderWay ? 1 : -1;

        usort(
            $messages,
            static fn(IntegrationInboxMessage $a, IntegrationInboxMessage $b): int => $way * ($a->receivedAt() <=> $b->receivedAt()),
        );

        if ($filter->limit > 0) {
            return array_slice($messages, $filter->page * $filter->limit, $filter->limit);
        }

        return $messages;
    }

    public function purgeReceivedBefore(\DateTimeImmutable $limit): int
    {
        $removed = 0;

        foreach ($this->messages as $messageId => $message) {
            if ($message->receivedAt() < $limit) {
                unset($this->messages[$messageId]);
                ++$removed;
            }
        }

        return $removed;
    }
}
