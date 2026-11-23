<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\InMemory\Repository;

use App\Shared\Domain\Model\Integration\IntegrationOutboxFilter;
use App\Shared\Domain\Model\Integration\IntegrationOutboxMessage;
use App\Shared\Domain\Model\Integration\IntegrationOutboxRepository;
use App\Shared\Domain\Model\Integration\IntegrationOutboxStatus;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryIntegrationOutboxRepository implements IntegrationOutboxRepository
{
    /** @var array<int, IntegrationOutboxMessage> */
    private array $messages = [];

    private int $lastSequence = 0;

    public function add(IntegrationOutboxMessage $message): void
    {
        if (null === $message->sequence()) {
            $this->assignSequence($message, ++$this->lastSequence);
        }

        $this->messages[(int) $message->sequence()] = $message;
    }

    public function save(IntegrationOutboxMessage $message): void
    {
        $this->add($message);
    }

    public function pending(int $limit): array
    {
        $pending = array_values(array_filter(
            $this->sortedBySequence('ASC'),
            static fn(IntegrationOutboxMessage $message): bool => !$message->isSent(),
        ));

        return array_slice($pending, 0, $limit);
    }

    public function oldestPendingOccurredOn(): ?\DateTimeImmutable
    {
        $oldest = null;

        foreach ($this->messages as $message) {
            if ($message->isSent()) {
                continue;
            }

            if (null === $oldest || $message->occurredOn() < $oldest) {
                $oldest = $message->occurredOn();
            }
        }

        return $oldest;
    }

    public function list(IntegrationOutboxFilter $filter): array
    {
        $messages = array_values(array_filter(
            $this->sortedBySequence('ASC' === $filter->orderWay ? 'ASC' : 'DESC'),
            static fn(IntegrationOutboxMessage $message): bool => match ($filter->status) {
                IntegrationOutboxStatus::Pending => !$message->isSent(),
                IntegrationOutboxStatus::Sent => $message->isSent(),
                default => true,
            } && (null === $filter->type || '' === $filter->type || $message->type() === $filter->type),
        ));

        if ($filter->limit > 0) {
            return array_slice($messages, $filter->page * $filter->limit, $filter->limit);
        }

        return $messages;
    }

    public function purgeSentBefore(\DateTimeImmutable $limit): int
    {
        $removed = 0;

        foreach ($this->messages as $sequence => $message) {
            $sentAt = $message->sentAt();

            if (null !== $sentAt && $sentAt < $limit) {
                unset($this->messages[$sequence]);
                ++$removed;
            }
        }

        return $removed;
    }

    /** @return list<IntegrationOutboxMessage> */
    private function sortedBySequence(string $way): array
    {
        $messages = $this->messages;

        'ASC' === $way ? ksort($messages) : krsort($messages);

        return array_values($messages);
    }

    private function assignSequence(IntegrationOutboxMessage $message, int $sequence): void
    {
        $property = new \ReflectionProperty(IntegrationOutboxMessage::class, 'sequence');
        $property->setValue($message, $sequence);
    }
}
