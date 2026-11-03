<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprint;

enum MaterialityCategoryState: string
{
    case WithoutScore           = 'without_score';
    case WithoutSource          = 'without_source';
    case NotApplicableToSubject = 'not_applicable_to_subject';
    case StageExcluded          = 'stage_excluded';
    case ExcludedRecommended    = 'excluded_recommended';
    case Active                 = 'active';
    case ActiveOverridden       = 'active_overridden';
    case ExcludedOverridden     = 'excluded_overridden';
    case PendingReview          = 'pending_review';
    case NotEvaluated           = 'not_evaluated';

    public function style(): string
    {
        return match ($this) {
            self::Active => 'bg-success',
            self::ExcludedRecommended => 'bg-secondary',
            self::ActiveOverridden, self::ExcludedOverridden => 'bg-info',
            self::PendingReview => 'bg-warning text-dark',
            self::StageExcluded => 'bg-dark',
            self::WithoutScore, self::WithoutSource, self::NotApplicableToSubject, self::NotEvaluated => 'bg-light text-dark',
        };
    }
}
