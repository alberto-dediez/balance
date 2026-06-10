<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Alert;

enum AlertPriority: string
{
    case Info = 'Info';
    case Warning = 'Warning';
    case Critical = 'Critical';

    public function style(): string
    {
        return match ($this) {
            self::Info => 'bg-info',
            self::Warning => 'bg-warning',
            self::Critical => 'bg-danger',
        };
    }
}
