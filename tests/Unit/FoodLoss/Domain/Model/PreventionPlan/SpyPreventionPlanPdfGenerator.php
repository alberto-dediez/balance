<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanPdfGenerator;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersion;
use App\Shared\Domain\Model\Locale\Locale;

final class SpyPreventionPlanPdfGenerator implements PreventionPlanPdfGenerator
{
    /** @var array<int, array{plan: PreventionPlan, version: PreventionPlanVersion}> */
    private array $calls = [];

    public function generate(PreventionPlan $preventionPlan, PreventionPlanVersion $version, Locale $locale): string
    {
        $this->calls[] = ['plan' => $preventionPlan, 'version' => $version];

        return '/tmp/test.pdf';
    }

    public function callCount(): int
    {
        return count($this->calls);
    }

    public function lastPlan(): ?PreventionPlan
    {
        return $this->calls[count($this->calls) - 1]['plan'] ?? null;
    }

    public function lastVersion(): ?PreventionPlanVersion
    {
        return $this->calls[count($this->calls) - 1]['version'] ?? null;
    }
}
