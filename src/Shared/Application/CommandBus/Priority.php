<?php

declare(strict_types=1);

namespace App\Shared\Application\CommandBus;

enum Priority
{
    case HIGH;
    case MEDIUM;
    case LOW;
}
