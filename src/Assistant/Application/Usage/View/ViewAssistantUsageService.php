<?php

declare(strict_types=1);

namespace App\Assistant\Application\Usage\View;

use App\Assistant\Application\Conversation\ConversationAuthorizer;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanRepository;
use App\Assistant\Domain\Model\AssistantUsageRow;
use App\Assistant\Domain\Model\Conversation\Conversation;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Assistant\Domain\Model\Conversation\ConversationRepository;
use App\Security\Application\Action;
use App\Security\Application\User\UserFinder;
use App\Security\Domain\Model\User\User;

class ViewAssistantUsageService
{
    public const string UNKNOWN_USER = '---';
    public const string UNKNOWN_MODEL = '---';

    public function __construct(
        private readonly ConversationRepository $conversations,
        private readonly ActionPlanRepository $plans,
        private readonly ConversationAuthorizer $authorizer,
        private readonly UserFinder $userFinder,
    ) {}

    public function __invoke(ViewAssistantUsageRequest $request, User $user): ViewAssistantUsageResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Conversation(ConversationId::generate()), Action::Admin, $user);

        $from = $request->period->from;
        $to = $request->period->to;

        $totals = new AssistantUsageTotals();
        $days = [];
        $users = [];
        $models = [];

        $rows = [...$this->conversations->usageRowsBetween($from, $to), ...$this->plans->usageRowsBetween($from, $to)];

        foreach ($rows as $row) {
            $totals = $this->sum($totals, $row);

            $day = $row->day->format('Y-m-d');
            $days[$day] = $this->sum($days[$day] ?? new AssistantUsageTotals(), $row);

            $name = null === $row->userId ? self::UNKNOWN_USER : ($this->userFinder->find($row->userId)?->username() ?? self::UNKNOWN_USER);
            $users[$name] = $this->sum($users[$name] ?? new AssistantUsageTotals(), $row);

            $model = $row->model ?? self::UNKNOWN_MODEL;
            $models[$model] = $this->sum($models[$model] ?? new AssistantUsageTotals(), $row);
        }

        krsort($days);
        uasort($users, static fn(AssistantUsageTotals $a, AssistantUsageTotals $b): int => (float) $b->costUsd <=> (float) $a->costUsd);
        uasort($models, static fn(AssistantUsageTotals $a, AssistantUsageTotals $b): int => (float) $b->costUsd <=> (float) $a->costUsd);

        return new ViewAssistantUsageResponse($totals, $days, $users, $models);
    }

    private function sum(AssistantUsageTotals $totals, AssistantUsageRow $row): AssistantUsageTotals
    {
        return $totals->add($row->messages, $row->inputTokens, $row->outputTokens, $row->costUsd);
    }
}
