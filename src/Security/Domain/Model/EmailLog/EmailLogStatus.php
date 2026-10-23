<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\EmailLog;

enum EmailLogStatus: string
{
    case Sent = 'Sent';
    case Failed = 'Failed';
    case Deferred = 'Deferred';
    case Delivered = 'Delivered';
    case Bounced = 'Bounced';
    case Dropped = 'Dropped';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Failed, self::Bounced, self::Dropped => true,
            default => false,
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Sent => 'info',
            self::Deferred => 'warning',
            self::Delivered => 'success',
            self::Failed, self::Bounced, self::Dropped => 'danger',
        };
    }
}
