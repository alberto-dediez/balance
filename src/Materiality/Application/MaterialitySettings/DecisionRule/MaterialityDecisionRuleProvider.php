<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialitySettings\DecisionRule;

use App\Materiality\Domain\Model\MaterialitySettings\MaterialityDecisionRule;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettingsRepository;

class MaterialityDecisionRuleProvider
{
    public function __construct(
        private MaterialitySettingsRepository $materialitySettingsRepository,
    ) {}

    public function rule(): MaterialityDecisionRule
    {
        return $this->materialitySettingsRepository->findCurrent()?->rule() ?? MaterialityDecisionRule::default();
    }
}
