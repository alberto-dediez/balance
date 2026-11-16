<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\ActionPlan;

use App\Assistant\Domain\Model\AssistantUsage;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class ActionPlan
{
    use AggregateRoot;

    public const int MAX_STEPS = 15;
    public const int MAX_REJECTED_CALLS = 3;
    public const string REASON_INCOMPLETE = 'El modelo no completó el plan';
    public const string REASON_TOO_MANY_REJECTED_CALLS = 'Demasiadas llamadas rechazadas';

    /**
     * @param Collection<int, PlanStep> $steps
     * @param DomainEvent[]             $events
     */
    public function __construct(
        private ActionPlanId $id,
        private ?ConversationId $conversationId = null,
        private ?UserId $userId = null,
        private string $summary = '',
        private string $timezone = 'UTC',
        private ActionPlanStatus $status = ActionPlanStatus::Proposed,
        private ?string $failureReason = null,
        private ?DiscardReason $discardReason = null,
        private ?UserId $approvedBy = null,
        private \DateTimeImmutable $proposedAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $approvedAt = null,
        private ?\DateTimeImmutable $startedAt = null,
        private ?\DateTimeImmutable $finishedAt = null,
        private ?\DateTimeImmutable $lastProgressAt = null,
        private int $rejectedCalls = 0,
        private AssistantUsage $usage = new AssistantUsage(),
        private Collection $steps = new ArrayCollection(),
        private int $version = 1,
        array $events = [],
    ) {
        $this->events = $events;
    }

    public function id(): ActionPlanId
    {
        return $this->id;
    }

    public function conversationId(): ConversationId
    {
        if (null === $this->conversationId) {
            throw new \LogicException(sprintf('Action plan %s has not been proposed yet', $this->id));
        }

        return $this->conversationId;
    }

    public function userId(): ?UserId
    {
        return $this->userId;
    }

    public function summary(): string
    {
        return $this->summary;
    }

    public function timezone(): string
    {
        return $this->timezone;
    }

    public function status(): ActionPlanStatus
    {
        return $this->status;
    }

    public function failureReason(): ?string
    {
        return $this->failureReason;
    }

    public function discardReason(): ?DiscardReason
    {
        return $this->discardReason;
    }

    public function approvedBy(): ?UserId
    {
        return $this->approvedBy;
    }

    public function proposedAt(): \DateTimeImmutable
    {
        return $this->proposedAt;
    }

    public function approvedAt(): ?\DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function startedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function finishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function lastProgressAt(): ?\DateTimeImmutable
    {
        return $this->lastProgressAt;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function rejectedCalls(): int
    {
        return $this->rejectedCalls;
    }

    public function usage(): AssistantUsage
    {
        return $this->usage;
    }

    /** @return list<PlanStep> */
    public function steps(): array
    {
        $steps = array_values($this->steps->toArray());

        usort($steps, static fn(PlanStep $a, PlanStep $b): int => $a->position() <=> $b->position());

        return $steps;
    }

    public function stepAt(int $position): ?PlanStep
    {
        foreach ($this->steps as $step) {
            if ($step->position() === $position) {
                return $step;
            }
        }

        return null;
    }

    public function nextPendingStep(): ?PlanStep
    {
        foreach ($this->steps() as $step) {
            if ($step->isPending()) {
                return $step;
            }
        }

        return null;
    }

    public function failedStep(): ?PlanStep
    {
        foreach ($this->steps() as $step) {
            if (PlanStepStatus::Failed === $step->status()) {
                return $step;
            }
        }

        return null;
    }

    public function doneSteps(): int
    {
        return count(array_filter($this->steps(), static fn(PlanStep $step): bool => $step->isDone()));
    }

    public function isOf(UserId $userId): bool
    {
        return null !== $this->userId && $this->userId->equals($userId);
    }

    public function isProposed(): bool
    {
        return ActionPlanStatus::Proposed === $this->status;
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isExpiredAt(\DateTimeImmutable $now, int $ttlMinutes): bool
    {
        return $now->getTimestamp() - $this->proposedAt->getTimestamp() > $ttlMinutes * 60;
    }

    public function hasTooManyRejectedCalls(): bool
    {
        return $this->rejectedCalls >= self::MAX_REJECTED_CALLS;
    }

    /** @param list<ProposedStep> $steps */
    public function propose(ConversationId $conversationId, UserId $userId, string $summary, string $timezone, array $steps, \DateTimeImmutable $now): void
    {
        $summary = trim($summary);

        if ('' === $summary) {
            throw InvalidPlanException::because('El plan necesita un resumen.');
        }

        if ([] === $steps) {
            throw InvalidPlanException::because('El plan necesita al menos un paso.');
        }

        if (count($steps) > self::MAX_STEPS) {
            throw InvalidPlanException::because(sprintf('El plan no puede tener más de %d pasos.', self::MAX_STEPS));
        }

        $payload = [];

        foreach ($steps as $index => $step) {
            $position = $index + 1;
            $arguments = [];

            foreach ($step->arguments as $argument) {
                if (null !== $argument->fromStep && ($argument->fromStep < 1 || $argument->fromStep >= $position)) {
                    throw InvalidPlanException::because(sprintf('Paso %d: «%s» solo puede usar el resultado de un paso anterior.', $position, $argument->name));
                }

                $arguments[] = [
                    'name' => $argument->name,
                    'value' => $argument->value,
                    'display' => $argument->display,
                    'fromStep' => $argument->fromStep,
                ];
            }

            $payload[] = [
                'stepId' => (string) PlanStepId::generate(),
                'position' => $position,
                'tool' => $step->tool,
                'label' => $step->label,
                'arguments' => $arguments,
            ];
        }

        $this->recordApplyAndPublish(new ActionPlanProposed(
            planId: $this->id,
            conversationId: $conversationId,
            userId: $userId,
            summary: $summary,
            timezone: $timezone,
            steps: $payload,
            occurredOn: $now,
        ));
    }

    public function approve(UserId $by, \DateTimeImmutable $now, int $ttlMinutes): void
    {
        if (!$this->isProposed()) {
            throw PlanNotExecutableException::notProposed();
        }

        if ($this->isExpiredAt($now, $ttlMinutes)) {
            throw PlanNotExecutableException::expired();
        }

        if (!$this->isOf($by)) {
            throw PlanNotExecutableException::notOwner();
        }

        $this->recordApplyAndPublish(new ActionPlanApproved(
            planId: $this->id,
            approvedBy: $by,
            occurredOn: $now,
        ));
    }

    public function start(\DateTimeImmutable $now): void
    {
        if (ActionPlanStatus::Approved !== $this->status) {
            throw PlanNotExecutableException::notApproved();
        }

        $this->recordApplyAndPublish(new ActionPlanExecutionStarted(
            planId: $this->id,
            occurredOn: $now,
        ));
    }

    /**
     * @param array<string, string> $references
     * @param list<string>          $linkParameters
     */
    public function completeStep(int $position, string $summary, array $references, ?string $linkLabel, ?string $linkRoute, array $linkParameters, \DateTimeImmutable $now): void
    {
        $step = $this->assertNextStep($position);

        $this->recordApplyAndPublish(new ActionPlanStepExecuted(
            planId: $this->id,
            position: $position,
            tool: $step->tool(),
            summary: $summary,
            outputs: $references,
            linkLabel: $linkLabel,
            linkRoute: $linkRoute,
            linkParameters: $linkParameters,
            occurredOn: $now,
        ));
    }

    public function failStep(int $position, string $error, \DateTimeImmutable $now): void
    {
        $step = $this->assertNextStep($position);

        $this->recordApplyAndPublish(new ActionPlanStepFailed(
            planId: $this->id,
            position: $position,
            tool: $step->tool(),
            error: $error,
            occurredOn: $now,
        ));
    }

    public function rejectCall(): void
    {
        ++$this->rejectedCalls;
    }

    public function finish(AssistantUsage $usage, \DateTimeImmutable $now): void
    {
        if (ActionPlanStatus::Executing !== $this->status) {
            throw PlanNotExecutableException::notExecuting();
        }

        $total = $this->usage->add($usage);

        if (null === $this->nextPendingStep() && null === $this->failedStep()) {
            $this->recordApplyAndPublish(new ActionPlanExecuted(
                planId: $this->id,
                usage: $total,
                occurredOn: $now,
            ));

            return;
        }

        $this->recordApplyAndPublish(new ActionPlanFailed(
            planId: $this->id,
            reason: $this->failedStep()?->error() ?? ($this->hasTooManyRejectedCalls() ? self::REASON_TOO_MANY_REJECTED_CALLS : self::REASON_INCOMPLETE),
            usage: $total,
            occurredOn: $now,
        ));
    }

    public function interrupt(string $reason, \DateTimeImmutable $now, ?AssistantUsage $usage = null): void
    {
        if (!$this->isActive()) {
            throw PlanNotExecutableException::notExecuting();
        }

        $this->recordApplyAndPublish(new ActionPlanFailed(
            planId: $this->id,
            reason: $reason,
            usage: $this->usage->add($usage ?? AssistantUsage::none()),
            occurredOn: $now,
        ));
    }

    public function discard(DiscardReason $reason, \DateTimeImmutable $now): void
    {
        if (!$this->isProposed()) {
            throw PlanNotExecutableException::notProposed();
        }

        $this->recordApplyAndPublish(new ActionPlanDiscarded(
            planId: $this->id,
            reason: $reason,
            occurredOn: $now,
        ));
    }

    public function expire(\DateTimeImmutable $now): void
    {
        if (!$this->isProposed()) {
            throw PlanNotExecutableException::notProposed();
        }

        $this->recordApplyAndPublish(new ActionPlanExpired(
            planId: $this->id,
            occurredOn: $now,
        ));
    }

    public function applyActionPlanProposed(ActionPlanProposed $event): void
    {
        $this->conversationId = $event->conversationId();
        $this->userId = $event->userId();
        $this->summary = $event->summary();
        $this->timezone = $event->timezone();
        $this->status = ActionPlanStatus::Proposed;
        $this->proposedAt = $event->occurredOn();
        $this->steps->clear();

        foreach ($event->steps() as $data) {
            $step = new PlanStep(new PlanStepId($data['stepId']), $this, $data['position'], $data['tool'], $data['label']);

            foreach ($data['arguments'] as $argument) {
                $step->addArgument($argument['name'], $argument['value'], $argument['display'], $argument['fromStep']);
            }

            $this->steps->add($step);
        }
    }

    public function applyActionPlanApproved(ActionPlanApproved $event): void
    {
        $this->status = ActionPlanStatus::Approved;
        $this->approvedBy = $event->approvedBy();
        $this->approvedAt = $event->occurredOn();
    }

    public function applyActionPlanExecutionStarted(ActionPlanExecutionStarted $event): void
    {
        $this->status = ActionPlanStatus::Executing;
        $this->startedAt = $event->occurredOn();
        $this->lastProgressAt = $event->occurredOn();
    }

    public function applyActionPlanStepExecuted(ActionPlanStepExecuted $event): void
    {
        $this->stepAt($event->position())?->complete(
            $event->summary(),
            $event->outputs(),
            $event->linkLabel(),
            $event->linkRoute(),
            $event->linkParameters(),
            $event->occurredOn(),
        );
        $this->lastProgressAt = $event->occurredOn();
    }

    public function applyActionPlanStepFailed(ActionPlanStepFailed $event): void
    {
        $this->stepAt($event->position())?->fail($event->error(), $event->occurredOn());
        $this->skipPendingSteps();
        $this->lastProgressAt = $event->occurredOn();
    }

    public function applyActionPlanExecuted(ActionPlanExecuted $event): void
    {
        $this->status = ActionPlanStatus::Executed;
        $this->usage = $event->usage();
        $this->finishedAt = $event->occurredOn();
    }

    public function applyActionPlanFailed(ActionPlanFailed $event): void
    {
        $this->skipPendingSteps();
        $this->status = ActionPlanStatus::Failed;
        $this->failureReason = $event->reason();
        $this->usage = $event->usage();
        $this->finishedAt = $event->occurredOn();
    }

    public function applyActionPlanDiscarded(ActionPlanDiscarded $event): void
    {
        $this->status = ActionPlanStatus::Discarded;
        $this->discardReason = $event->reason();
        $this->finishedAt = $event->occurredOn();
    }

    public function applyActionPlanExpired(ActionPlanExpired $event): void
    {
        $this->status = ActionPlanStatus::Expired;
        $this->finishedAt = $event->occurredOn();
    }

    private function assertNextStep(int $position): PlanStep
    {
        if (ActionPlanStatus::Executing !== $this->status) {
            throw PlanNotExecutableException::notExecuting();
        }

        $step = $this->nextPendingStep();

        if (null === $step || $step->position() !== $position) {
            throw PlanNotExecutableException::notNextStep($position);
        }

        return $step;
    }

    private function skipPendingSteps(): void
    {
        foreach ($this->steps as $step) {
            if ($step->isPending()) {
                $step->skip();
            }
        }
    }
}
