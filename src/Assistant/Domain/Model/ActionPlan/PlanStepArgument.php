<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\ActionPlan;

class PlanStepArgument
{
    public const int DISPLAY_MAX_LENGTH = 255;

    public function __construct(
        private PlanStep $step,
        private string $name,
        private int $position,
        private string $value,
        private ?string $display = null,
        private ?int $fromStep = null,
        private ?int $id = null,
    ) {}

    public function id(): ?int
    {
        return $this->id;
    }

    public function step(): PlanStep
    {
        return $this->step;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function display(): ?string
    {
        return $this->display;
    }

    public function fromStep(): ?int
    {
        return $this->fromStep;
    }

    public function isDeferred(): bool
    {
        return null !== $this->fromStep;
    }
}
