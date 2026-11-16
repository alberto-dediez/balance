<?php

declare(strict_types=1);

namespace App\Assistant\Application\Engine;

use App\Assistant\Application\AssistantSettings;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanRepository;
use App\Assistant\Domain\Model\AssistantUsage;
use App\Assistant\Domain\Model\Conversation\ConversationRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Clock\Clock;

class DailyBudget
{
    public function __construct(
        private readonly ConversationRepository $conversations,
        private readonly ActionPlanRepository $plans,
        private readonly AssistantSettings $settings,
        private readonly Clock $clock,
    ) {}

    public function spentToday(User $user): string
    {
        $since = $this->clock->now()->setTime(0, 0);

        return AssistantUsage::formatCost(
            (float) $this->conversations->costOfUserSince($user->id(), $since)
            + (float) $this->plans->costOfUserSince($user->id(), $since),
        );
    }

    public function limit(): string
    {
        return AssistantUsage::formatCost((float) $this->settings->dailyCostLimitUsd);
    }

    public function assertAvailable(User $user): void
    {
        if ($this->settings->hasDailyLimit() && (float) $this->spentToday($user) >= (float) $this->settings->dailyCostLimitUsd) {
            throw new DailyLimitReachedException();
        }
    }
}
