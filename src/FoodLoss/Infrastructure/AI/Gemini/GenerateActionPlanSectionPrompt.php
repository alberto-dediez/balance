<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\AI\Gemini;

use App\FoodLoss\Domain\Model\Action\Action;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\FoodLoss\Domain\Model\PreventionPlan\ProposedAction;
use App\Property\Domain\Model\Property\Property;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\AI\Gemini\GeminiPrompt;

final class GenerateActionPlanSectionPrompt implements GeminiPrompt
{
    /**
     * @param ProposedAction[]             $proposedActions
     * @param array<string, Property>      $propertyMap      indexed by property ID string
     * @param array<string, Action>        $actionMap        indexed by action ID string
     * @param array<string, CriticalPoint> $criticalPointMap indexed by critical point ID string
     */
    public function __construct(
        private readonly array $proposedActions,
        private readonly array $propertyMap,
        private readonly array $actionMap,
        private readonly array $criticalPointMap,
        private readonly Locale $locale = Locale::Spanish,
        private readonly float $temperature = 0.4,
    ) {}

    public function text(): string
    {
        $byPriority = $this->groupByPriority();

        $lines = [
            'You are an expert consultant in food safety regulations and food waste prevention in Spain.',
            '',
            'Write the "Action Plan and Prevention Hierarchy" section of a Food Waste Prevention Plan compliant with Spanish Law 1/2025 (Ley 1/2025) for the HORECA sector.',
            '',
            sprintf('The output must be written in %s and formatted in Markdown.', $this->locale->name),
            '',
            'Spanish Law 1/2025 establishes a strict five-level hierarchy for managing food surplus. Present the agreed actions following this legal order.',
            'For each action, explain proactively how it complies with the law.',
            '',
            '## Level 1 — Prevention (highest priority)',
            'Actions that prevent food waste at source before it is generated.',
            '',
        ];

        $lines = array_merge($lines, $this->formatActionsForPriority($byPriority, Priority::Prevention));

        $lines[] = '';
        $lines[] = '## Level 2 — Human donation (second priority)';
        $lines[] = 'Surplus safe food prioritised for human consumption through donation to social entities.';
        $lines[] = '';

        $lines = array_merge($lines, $this->formatActionsForPriority($byPriority, Priority::HumanDonation));

        $lines[] = '';
        $lines[] = '## Level 3 — Animal feed (third priority)';
        $lines[] = 'Food not suitable for human consumption used as animal feed.';
        $lines[] = '';

        $lines = array_merge($lines, $this->formatActionsForPriority($byPriority, Priority::AnimalFeed));

        $lines[] = '';
        $lines[] = '## Level 4 — Transformation (fourth priority)';
        $lines[] = 'Processing surplus into new food products (e.g. jams from overripe fruit, stocks from vegetable trimmings).';
        $lines[] = '';

        $lines = array_merge($lines, $this->formatActionsForPriority($byPriority, Priority::Transformation));

        $lines[] = '';
        $lines[] = '## Level 5 — Composting (fifth priority)';
        $lines[] = 'Biological recovery of food waste when no higher-priority option is feasible.';
        $lines[] = '';

        $lines = array_merge($lines, $this->formatActionsForPriority($byPriority, Priority::Composting));

        $lines[] = '';
        $lines[] = '## Mandatory protocol: leftover containers for customers';
        $lines[] = 'Law 1/2025 requires all HORECA establishments to offer customers a way to take home their unconsumed food at no extra charge.';
        $lines[] = 'Write a dedicated subsection describing the protocol for this obligation: offering recyclable food-safe containers to customers, staff training, and how it is communicated at point of service.';
        $lines[] = '';
        $lines[] = 'Use H2/H3 headings and bullet points. Each action entry must reference its property and critical point.';

        return implode("\n", $lines);
    }

    /** @return array<mixed> */
    public function responseSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'title' => ['type' => 'STRING'],
                'content' => ['type' => 'STRING'],
            ],
            'required' => ['title', 'content'],
        ];
    }

    public function temperature(): float
    {
        return $this->temperature;
    }

    /** @return array<string, list<array{action: Action, property: ?Property, criticalPoint: ?CriticalPoint}>> */
    private function groupByPriority(): array
    {
        $byPriority = [];

        foreach ($this->proposedActions as $pa) {
            $action = $this->actionMap[(string) $pa->actionId()] ?? null;
            if (null === $action) {
                continue;
            }

            $byPriority[$action->priority()->value][] = [
                'action' => $action,
                'property' => $this->propertyMap[(string) $pa->propertyId()] ?? null,
                'criticalPoint' => $this->criticalPointMap[(string) $pa->criticalPointId()] ?? null,
            ];
        }

        return $byPriority;
    }

    /**
     * @param array<string, list<array{action: Action, property: ?Property, criticalPoint: ?CriticalPoint}>> $byPriority
     *
     * @return string[]
     */
    private function formatActionsForPriority(array $byPriority, Priority $priority): array
    {
        $items = $byPriority[$priority->value] ?? [];

        if ([] === $items) {
            return ['- No actions proposed for this level.'];
        }

        $lines = [];
        foreach ($items as $item) {
            $lines[] = sprintf(
                '- %s | Property: %s | Critical point: %s',
                $item['action']->description(),
                $item['property']?->name() ?? '[Unknown property]',
                $item['criticalPoint']?->name() ?? '[Unknown critical point]',
            );
        }

        return $lines;
    }
}
