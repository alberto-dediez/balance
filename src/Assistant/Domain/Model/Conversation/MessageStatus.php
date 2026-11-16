<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\Conversation;

enum MessageStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Completed => 'success',
            self::Failed => 'danger',
        };
    }
}
