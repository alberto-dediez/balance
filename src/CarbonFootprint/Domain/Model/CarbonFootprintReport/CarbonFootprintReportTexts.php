<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

final class CarbonFootprintReportTexts
{
    /** @param array<string, string> $sections */
    public function __construct(public readonly array $sections) {}

    public function get(CarbonFootprintReportSectionKey $key): string
    {
        return $this->sections[$key->value] ?? '';
    }

    public function has(CarbonFootprintReportSectionKey $key): bool
    {
        return '' !== trim($this->get($key));
    }

    public function isCompleteFor(CarbonFootprintReportSubjectType $type): bool
    {
        foreach (CarbonFootprintReportSectionKey::requiredFor($type) as $key) {
            if (!$this->has($key)) {
                return false;
            }
        }

        return true;
    }
}
