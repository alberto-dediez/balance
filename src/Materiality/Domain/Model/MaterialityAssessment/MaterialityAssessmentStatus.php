<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityAssessment;

enum MaterialityAssessmentStatus: string
{
    case Draft = 'draft';

    case Completed = 'completed';

    public function isCompleted(): bool
    {
        return self::Completed === $this;
    }

    public function style(): string
    {
        return match ($this) {
            self::Draft => 'bg-warning text-dark',
            self::Completed => 'bg-success',
        };
    }
}
