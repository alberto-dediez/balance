<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Domain\Model\Conversation;

use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Assistant\Domain\Model\AssistantUsage;
use App\Assistant\Domain\Model\Conversation\Conversation;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Assistant\Domain\Model\Conversation\MessageKind;
use App\Security\Domain\Model\User\UserId;

class ConversationMother
{
    public static function startedBy(UserId $userId, ?\DateTimeImmutable $now = null): Conversation
    {
        $conversation = new Conversation(ConversationId::generate());
        $conversation->start($userId, $now ?? new \DateTimeImmutable('2026-11-11 10:00:00'));

        return $conversation;
    }

    public static function withQuestion(UserId $userId, string $text = '¿Qué tareas tiene Acme?', ?\DateTimeImmutable $now = null): Conversation
    {
        $now ??= new \DateTimeImmutable('2026-11-11 10:00:00');
        $conversation = self::startedBy($userId, $now);
        $conversation->addUserMessage(MessageId::generate(), $text, '/es/admin/tasks', 'Tareas', 'Europe/Madrid', $now);

        return $conversation;
    }

    public static function waitingForAnswer(UserId $userId, ?MessageId $assistantMessageId = null, ?\DateTimeImmutable $now = null): Conversation
    {
        $now ??= new \DateTimeImmutable('2026-11-11 10:00:00');
        $conversation = self::withQuestion($userId, now: $now);
        $conversation->addPendingAssistantMessage($assistantMessageId ?? MessageId::generate(), MessageKind::Text, $now);

        return $conversation;
    }

    public static function answered(UserId $userId, string $answer = 'Acme tiene 2 tareas.', string $cost = '0.000200', ?\DateTimeImmutable $now = null): Conversation
    {
        $now ??= new \DateTimeImmutable('2026-11-11 10:00:00');
        $messageId = MessageId::generate();
        $conversation = self::waitingForAnswer($userId, $messageId, $now);
        $conversation->completeMessage($messageId, $answer, MessageKind::Text, null, new AssistantUsage('in-memory', 100, 20, 0, 0, $cost, 10), $now);

        return $conversation;
    }

    public static function withPlan(UserId $userId, ActionPlanId $planId, ?\DateTimeImmutable $now = null): Conversation
    {
        $now ??= new \DateTimeImmutable('2026-11-11 10:00:00');
        $messageId = MessageId::generate();
        $conversation = self::waitingForAnswer($userId, $messageId, $now);
        $conversation->completeMessage($messageId, 'Crear la tarea', MessageKind::Plan, $planId, AssistantUsage::none(), $now);

        return $conversation;
    }
}
