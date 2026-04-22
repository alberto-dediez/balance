<?php

declare(strict_types=1);

namespace App\Shared\Application\CommandBus;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
class TrackedCommand implements Command
{
    public function __construct(
        private readonly Command $command,
        private readonly string $progressKey,
        private readonly int $progressTotal,
    ) {}

    public function command(): Command
    {
        return $this->command;
    }

    public function progressKey(): string
    {
        return $this->progressKey;
    }

    public function progressTotal(): int
    {
        return $this->progressTotal;
    }
}
