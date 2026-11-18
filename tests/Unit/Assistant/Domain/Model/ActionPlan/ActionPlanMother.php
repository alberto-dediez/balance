<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Domain\Model\ActionPlan;

use App\Assistant\Domain\Model\ActionPlan\ActionPlan;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Assistant\Domain\Model\ActionPlan\ProposedArgument;
use App\Assistant\Domain\Model\ActionPlan\ProposedStep;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Security\Domain\Model\User\UserId;

class ActionPlanMother
{
    public const string PROJECT_ID = '0190f3a4-1b2c-7d3e-8f40-000000000001';

    /** @param list<ProposedStep>|null $steps */
    public static function proposed(UserId $userId, ?ConversationId $conversationId = null, ?array $steps = null, ?\DateTimeImmutable $now = null, ?ActionPlanId $id = null): ActionPlan
    {
        $plan = new ActionPlan($id ?? ActionPlanId::generate());
        $plan->propose(
            $conversationId ?? ConversationId::generate(),
            $userId,
            'Crear la tarea «Migrar DNS» con una acción',
            'Europe/Madrid',
            $steps ?? self::taskWithActionSteps(),
            $now ?? new \DateTimeImmutable('2026-11-11 10:00:00'),
        );

        return $plan;
    }

    public static function approved(UserId $userId, ?ConversationId $conversationId = null, ?\DateTimeImmutable $now = null, ?ActionPlanId $id = null): ActionPlan
    {
        $now ??= new \DateTimeImmutable('2026-11-11 10:00:00');
        $plan = self::proposed($userId, $conversationId, now: $now, id: $id);
        $plan->approve($userId, $now, 60);

        return $plan;
    }

    public static function executing(UserId $userId, ?ConversationId $conversationId = null, ?\DateTimeImmutable $now = null, ?ActionPlanId $id = null): ActionPlan
    {
        $now ??= new \DateTimeImmutable('2026-11-11 10:00:00');
        $plan = self::approved($userId, $conversationId, $now, $id);
        $plan->start($now);

        return $plan;
    }

    /** @return list<ProposedStep> */
    public static function taskWithActionSteps(): array
    {
        return [
            new ProposedStep('register_task', 'Registrar tarea', [
                new ProposedArgument('projectId', self::PROJECT_ID, 'Web Acme'),
                new ProposedArgument('title', 'Migrar DNS'),
            ]),
            new ProposedStep('add_task_action', 'Añadir acción', [
                new ProposedArgument('taskId', '', null, 1),
                new ProposedArgument('text', 'Bajar TTL'),
            ]),
        ];
    }
}
