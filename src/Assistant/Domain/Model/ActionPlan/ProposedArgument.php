<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\ActionPlan;

final readonly class ProposedArgument
{
    public function __construct(
        public string $name,
        public string $value,
        public ?string $display = null,
        public ?int $fromStep = null,
    ) {}
}
