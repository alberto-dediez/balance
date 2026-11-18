<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\ActionPlan;

class PlanStepOutput
{
    public function __construct(
        private PlanStep $step,
        private string $name,
        private string $value,
        private bool $linkParameter = false,
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

    public function value(): string
    {
        return $this->value;
    }

    public function isLinkParameter(): bool
    {
        return $this->linkParameter;
    }
}
