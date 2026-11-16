<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\Persistence\InMemory\Repository;

use App\Assistant\Domain\Model\AssistantUsage;
use App\Assistant\Domain\Model\AssistantUsageRowBuilder;
use App\Assistant\Domain\Model\Conversation\Conversation;
use App\Assistant\Domain\Model\Conversation\ConversationFilter;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Assistant\Domain\Model\Conversation\ConversationRepository;
use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Assistant\Domain\Model\Conversation\MessageStatus;
use App\Security\Domain\Model\User\UserId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryConversationRepository implements ConversationRepository
{
    /** @param array<string, Conversation> $conversations */
    public function __construct(private array $conversations = []) {}

    public function find(ConversationId $id): ?Conversation
    {
        return $this->conversations[(string) $id] ?? null;
    }

    public function add(Conversation $conversation): void
    {
        $this->conversations[(string) $conversation->id()] = $conversation;
    }

    public function messageStatusOf(MessageId $id): ?MessageStatus
    {
        foreach ($this->conversations as $conversation) {
            $message = $conversation->messageOf($id);

            if (null !== $message) {
                return $message->status();
            }
        }

        return null;
    }

    public function remove(Conversation $conversation): void
    {
        unset($this->conversations[(string) $conversation->id()]);
    }

    public function findByFilter(ConversationFilter $filter): array
    {
        $results = array_values(array_filter($this->conversations, fn(Conversation $conversation): bool => $this->matches($conversation, $filter)));

        usort($results, static fn(Conversation $a, Conversation $b): int => 'ASC' === $filter->orderWay
            ? $a->lastActivityAt() <=> $b->lastActivityAt()
            : $b->lastActivityAt() <=> $a->lastActivityAt());

        if ($filter->limit > 0) {
            $results = array_slice($results, $filter->page * $filter->limit, $filter->limit);
        }

        return $results;
    }

    public function totalResultsByFilter(ConversationFilter $filter): int
    {
        return count(array_filter($this->conversations, fn(Conversation $conversation): bool => $this->matches($conversation, $filter)));
    }

    public function costOfUserSince(UserId $userId, \DateTimeImmutable $since): string
    {
        $cost = 0.0;

        foreach ($this->conversations as $conversation) {
            if (!$conversation->isOf($userId)) {
                continue;
            }

            foreach ($conversation->messages() as $message) {
                if ($message->createdAt() >= $since) {
                    $cost += (float) $message->usage()->costUsd();
                }
            }
        }

        return AssistantUsage::formatCost($cost);
    }

    public function usageRowsBetween(?\DateTimeImmutable $from, ?\DateTimeImmutable $to): array
    {
        $builder = new AssistantUsageRowBuilder();

        foreach ($this->conversations as $conversation) {
            foreach ($conversation->messages() as $message) {
                if (!$message->isAssistant()) {
                    continue;
                }

                if (($from instanceof \DateTimeImmutable && $message->createdAt() < $from) || ($to instanceof \DateTimeImmutable && $message->createdAt() >= $to)) {
                    continue;
                }

                $usage = $message->usage();
                $builder->add($message->createdAt(), $conversation->userId(), $usage->model(), $usage->inputTokens(), $usage->outputTokens(), $usage->costUsd());
            }
        }

        return $builder->rows();
    }

    public function clear(): void
    {
        $this->conversations = [];
    }

    private function matches(Conversation $conversation, ConversationFilter $filter): bool
    {
        if ($filter->userId instanceof UserId && !$conversation->isOf($filter->userId)) {
            return false;
        }

        if ($filter->lastActivityBefore instanceof \DateTimeImmutable && $conversation->lastActivityAt() >= $filter->lastActivityBefore) {
            return false;
        }

        if ($filter->pendingMessageBefore instanceof \DateTimeImmutable) {
            $pending = $conversation->pendingMessage();

            if (null === $pending || $pending->createdAt() >= $filter->pendingMessageBefore) {
                return false;
            }
        }

        return true;
    }
}
