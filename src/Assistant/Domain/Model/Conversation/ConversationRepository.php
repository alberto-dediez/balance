<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\Conversation;

use App\Assistant\Domain\Model\AssistantUsageRow;
use App\Security\Domain\Model\User\UserId;

interface ConversationRepository
{
    public function find(ConversationId $id): ?Conversation;

    /** @throws ConversationBusyException */
    public function add(Conversation $conversation): void;

    public function messageStatusOf(MessageId $id): ?MessageStatus;

    public function remove(Conversation $conversation): void;

    /** @return list<Conversation> */
    public function findByFilter(ConversationFilter $filter): array;

    public function totalResultsByFilter(ConversationFilter $filter): int;

    public function costOfUserSince(UserId $userId, \DateTimeImmutable $since): string;

    /** @return list<AssistantUsageRow> */
    public function usageRowsBetween(?\DateTimeImmutable $from, ?\DateTimeImmutable $to): array;
}
