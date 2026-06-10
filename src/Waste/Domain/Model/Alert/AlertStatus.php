<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Alert;

enum AlertStatus: string
{
    case New = 'New';
    case Acknowledged = 'Acknowledged';

    public function style(): string
    {
        return match ($this) {
            self::Acknowledged => 'bg-light',
            self::New => 'bg-warning',
        };
    }
}
