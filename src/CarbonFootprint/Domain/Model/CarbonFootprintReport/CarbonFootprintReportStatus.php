<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

enum CarbonFootprintReportStatus: string
{
    case GeneratingTexts = 'generating_texts';
    case Draft           = 'draft';
    case PendingReview   = 'pending_review';
    case GeneratingPdf   = 'generating_pdf';
    case Generated       = 'generated';
    case Rejected        = 'rejected';
    case Failed          = 'failed';

    public function style(): string
    {
        return match ($this) {
            self::GeneratingTexts, self::GeneratingPdf => 'bg-info',
            self::Draft => 'bg-secondary',
            self::PendingReview => 'bg-warning text-dark',
            self::Generated => 'bg-success',
            self::Rejected, self::Failed => 'bg-danger',
        };
    }

    public function isGeneratingTexts(): bool
    {
        return self::GeneratingTexts === $this;
    }

    public function isDraft(): bool
    {
        return self::Draft === $this;
    }

    public function isPendingReview(): bool
    {
        return self::PendingReview === $this;
    }

    public function isGeneratingPdf(): bool
    {
        return self::GeneratingPdf === $this;
    }

    public function isGenerated(): bool
    {
        return self::Generated === $this;
    }

    public function isRejected(): bool
    {
        return self::Rejected === $this;
    }

    public function isFailed(): bool
    {
        return self::Failed === $this;
    }

    public function isInProgress(): bool
    {
        return self::GeneratingTexts === $this || self::GeneratingPdf === $this;
    }
}
