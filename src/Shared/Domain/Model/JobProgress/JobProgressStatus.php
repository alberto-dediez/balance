<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\JobProgress;

enum JobProgressStatus: string
{
    case InProgress = 'InProgress';
    case Completed = 'Completed';
    case Failed = 'Failed';

    public function isCompleted(): bool
    {
        return self::Completed === $this;
    }

    public function isFailed(): bool
    {
        return self::Failed === $this;
    }
}
