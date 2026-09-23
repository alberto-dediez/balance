<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

class CarbonFootprintReportSection
{
    private string $key;

    public function __construct(
        private CarbonFootprintReport $report,
        CarbonFootprintReportSectionKey $key,
        private string $content,
        private int $position,
    ) {
        $this->key = $key->value;
    }

    public function report(): CarbonFootprintReport
    {
        return $this->report;
    }

    public function key(): CarbonFootprintReportSectionKey
    {
        return CarbonFootprintReportSectionKey::from($this->key);
    }

    public function content(): string
    {
        return $this->content;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function changeContent(string $content, int $position): void
    {
        $this->content = $content;
        $this->position = $position;
    }
}
