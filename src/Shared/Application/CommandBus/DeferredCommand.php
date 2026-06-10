<?php

declare(strict_types=1);

namespace App\Shared\Application\CommandBus;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
class DeferredCommand implements Command
{
    public function __construct(
        private readonly Command $command,
        private readonly Priority $priority = Priority::MEDIUM,
    ) {}

    public function command(): Command
    {
        return $this->command;
    }

    public function priority(): Priority
    {
        return $this->priority;
    }
}
