<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContactRequest;

enum ContactRequestStatus: string
{
    case Pending = 'pending';
    case Managed = 'managed';
    case Discarded = 'discarded';

    public function style(): string
    {
        return match ($this) {
            self::Pending => 'bg-warning text-dark',
            self::Managed => 'bg-success',
            self::Discarded => 'bg-secondary',
        };
    }
}
