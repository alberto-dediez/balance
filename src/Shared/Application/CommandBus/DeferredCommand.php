<?php

namespace App\Shared\Application\CommandBus;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
class DeferredCommand implements Command
{
    public function __construct(
        private readonly Command $command
    ) {

    }

    public function command(): Command
    {
        return $this->command;
    }
}
