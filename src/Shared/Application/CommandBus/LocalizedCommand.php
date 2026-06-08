<?php

declare(strict_types=1);

namespace App\Shared\Application\CommandBus;

use App\Shared\Domain\Model\Locale\Locale;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
class LocalizedCommand implements Command
{
    public function __construct(
        private readonly Command $command,
        private readonly Locale $locale,
    ) {}

    public function command(): Command
    {
        return $this->command;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }
}
