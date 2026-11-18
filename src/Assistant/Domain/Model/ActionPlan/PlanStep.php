<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\ActionPlan;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class PlanStep
{
    /**
     * @param Collection<int, PlanStepArgument> $arguments
     * @param Collection<int, PlanStepOutput>   $outputs
     */
    public function __construct(
        private PlanStepId $id,
        private ActionPlan $plan,
        private int $position,
        private string $tool,
        private string $label,
        private PlanStepStatus $status = PlanStepStatus::Pending,
        private ?string $resultSummary = null,
        private ?string $error = null,
        private ?string $linkLabel = null,
        private ?string $linkRoute = null,
        private ?\DateTimeImmutable $executedAt = null,
        private Collection $arguments = new ArrayCollection(),
        private Collection $outputs = new ArrayCollection(),
    ) {}

    public function id(): PlanStepId
    {
        return $this->id;
    }

    public function plan(): ActionPlan
    {
        return $this->plan;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function tool(): string
    {
        return $this->tool;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function status(): PlanStepStatus
    {
        return $this->status;
    }

    public function resultSummary(): ?string
    {
        return $this->resultSummary;
    }

    public function error(): ?string
    {
        return $this->error;
    }

    public function linkLabel(): ?string
    {
        return $this->linkLabel;
    }

    public function linkRoute(): ?string
    {
        return $this->linkRoute;
    }

    public function executedAt(): ?\DateTimeImmutable
    {
        return $this->executedAt;
    }

    public function isPending(): bool
    {
        return PlanStepStatus::Pending === $this->status;
    }

    public function isDone(): bool
    {
        return PlanStepStatus::Done === $this->status;
    }

    /** @return list<PlanStepArgument> */
    public function argumentList(): array
    {
        $arguments = array_values($this->arguments->toArray());

        usort($arguments, static fn(PlanStepArgument $a, PlanStepArgument $b): int => $a->position() <=> $b->position());

        return $arguments;
    }

    /** @return array<string, list<string>> */
    public function arguments(): array
    {
        $values = [];

        foreach ($this->argumentList() as $argument) {
            if (!$argument->isDeferred()) {
                $values[$argument->name()][] = $argument->value();
            }
        }

        return $values;
    }

    /** @return array<string, int> */
    public function deferred(): array
    {
        $deferred = [];

        foreach ($this->argumentList() as $argument) {
            if (null !== $argument->fromStep()) {
                $deferred[$argument->name()] = $argument->fromStep();
            }
        }

        return $deferred;
    }

    /** @return list<PlanStepOutput> */
    public function outputs(): array
    {
        return array_values($this->outputs->toArray());
    }

    /** @return list<string> */
    public function outputValues(): array
    {
        return array_map(static fn(PlanStepOutput $output): string => $output->value(), $this->outputs());
    }

    /** @return array<string, string> */
    public function linkParameters(): array
    {
        $parameters = [];

        foreach ($this->outputs() as $output) {
            if ($output->isLinkParameter()) {
                $parameters[$output->name()] = $output->value();
            }
        }

        return $parameters;
    }

    public function hasLink(): bool
    {
        return null !== $this->linkRoute;
    }

    public function addArgument(string $name, string $value, ?string $display, ?int $fromStep): void
    {
        $this->arguments->add(new PlanStepArgument(
            $this,
            $name,
            $this->arguments->count() + 1,
            $value,
            null === $display ? null : mb_substr($display, 0, PlanStepArgument::DISPLAY_MAX_LENGTH),
            $fromStep,
        ));
    }

    /**
     * @param array<string, string> $references
     * @param list<string>          $linkParameters
     */
    public function complete(string $summary, array $references, ?string $linkLabel, ?string $linkRoute, array $linkParameters, \DateTimeImmutable $now): void
    {
        $this->status = PlanStepStatus::Done;
        $this->resultSummary = $summary;
        $this->linkLabel = $linkLabel;
        $this->linkRoute = $linkRoute;
        $this->executedAt = $now;

        foreach ($references as $name => $value) {
            $this->outputs->add(new PlanStepOutput($this, $name, $value, in_array($name, $linkParameters, true)));
        }
    }

    public function fail(string $error, \DateTimeImmutable $now): void
    {
        $this->status = PlanStepStatus::Failed;
        $this->error = $error;
        $this->executedAt = $now;
    }

    public function skip(): void
    {
        $this->status = PlanStepStatus::Skipped;
    }
}
