<?php

declare(strict_types=1);

namespace App\Assistant\Application;

use App\Shared\Application\Ai\AiEffort;
use App\Shared\Application\Ai\AiModelTier;

final readonly class AssistantSettings
{
    public AiModelTier $queryTier;
    public AiEffort $queryEffort;
    public AiModelTier $actionTier;
    public AiEffort $actionEffort;

    public function __construct(
        public bool $enabled = true,
        string $queryTier = 'low',
        string $queryEffort = 'medium',
        string $actionTier = 'high',
        string $actionEffort = 'medium',
        public string $dailyCostLimitUsd = '5',
        public int $retentionDays = 90,
        public int $maxToolRounds = 12,
        public int $planTtlMinutes = 60,
        public int $staleMinutes = 10,
        public int $documentMaxBytes = 10485760,
        public int $transcriptAttachmentsMaxBytes = 12582912,
    ) {
        $this->queryTier = AiModelTier::tryFrom(strtolower(trim($queryTier))) ?? AiModelTier::LOW;
        $this->queryEffort = AiEffort::tryFrom(strtolower(trim($queryEffort))) ?? AiEffort::MEDIUM;
        $this->actionTier = AiModelTier::tryFrom(strtolower(trim($actionTier))) ?? AiModelTier::HIGH;
        $this->actionEffort = AiEffort::tryFrom(strtolower(trim($actionEffort))) ?? AiEffort::MEDIUM;
    }

    public function hasDailyLimit(): bool
    {
        return (float) $this->dailyCostLimitUsd > 0;
    }
}
