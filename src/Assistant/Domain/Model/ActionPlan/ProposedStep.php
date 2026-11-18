<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\ActionPlan;

final readonly class ProposedStep
{
    /** @param list<ProposedArgument> $arguments */
    public function __construct(
        public string $tool,
        public string $label,
        public array $arguments,
    ) {}
}
