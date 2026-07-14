<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport;

enum EventCarbonFootprintReportStatus: string
{
    case Pending   = 'pending';
    case Generated = 'generated';
    case Failed    = 'failed';

    public function isPending(): bool
    {
        return self::Pending === $this;
    }

    public function isGenerated(): bool
    {
        return self::Generated === $this;
    }

    public function isFailed(): bool
    {
        return self::Failed === $this;
    }
}
