<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model;

use App\Security\Domain\Model\User\UserId;

final class AssistantUsageRowBuilder
{
    /** @var array<string, array{day: \DateTimeImmutable, userId: ?UserId, model: ?string, messages: int, inputTokens: int, outputTokens: int, cost: float}> */
    private array $groups = [];

    public function add(\DateTimeImmutable $at, ?UserId $userId, ?string $model, int $inputTokens, int $outputTokens, string $costUsd): void
    {
        $cost = (float) $costUsd;

        if (0 === $inputTokens && 0 === $outputTokens && $cost <= 0.0) {
            return;
        }

        $day = $at->setTime(0, 0);
        $key = sprintf('%s|%s|%s', $day->format('Y-m-d'), (string) $userId, (string) $model);

        $group = $this->groups[$key] ?? ['day' => $day, 'userId' => $userId, 'model' => $model, 'messages' => 0, 'inputTokens' => 0, 'outputTokens' => 0, 'cost' => 0.0];
        ++$group['messages'];
        $group['inputTokens'] += $inputTokens;
        $group['outputTokens'] += $outputTokens;
        $group['cost'] += $cost;
        $this->groups[$key] = $group;
    }

    /** @return list<AssistantUsageRow> */
    public function rows(): array
    {
        return array_values(array_map(
            static fn(array $group): AssistantUsageRow => new AssistantUsageRow(
                $group['day'],
                $group['userId'],
                $group['model'],
                $group['messages'],
                $group['inputTokens'],
                $group['outputTokens'],
                AssistantUsage::formatCost($group['cost']),
            ),
            $this->groups,
        ));
    }
}
