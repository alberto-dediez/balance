<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\AI\Gemini;

use App\FoodLoss\Domain\Model\Action\Action;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\Shared\Infrastructure\AI\Gemini\GeminiPrompt;

final class SuggestActionsPrompt implements GeminiPrompt
{
    /**
     * @param CriticalPoint[] $criticalPoints
     * @param Action[]        $actions
     */
    public function __construct(
        private readonly array $criticalPoints,
        private readonly array $actions,
        private readonly float $temperature = 0.2,
    ) {}

    public function text(): string
    {
        $lines = [
            'You are a food loss prevention expert. Based on the provided critical points and available actions, suggest the most relevant preventive actions for each critical point.',
            '',
            'Rules:',
            '- Suggest between 0 and 3 action IDs per critical point.',
            '- Only suggest actions whose priority matches one of the possible priorities of the critical point.',
            '- Only suggest actions that are genuinely relevant. Suggest an empty list if no action is appropriate.',
            '',
            'Critical Points:',
        ];

        foreach ($this->criticalPoints as $cp) {
            $possiblePriorities = array_values(array_filter([
                $cp->prevention()->isPossible ? 'Prevention' : null,
                $cp->humanDonation()->isPossible ? 'HumanDonation' : null,
                $cp->animalFeed()->isPossible ? 'AnimalFeed' : null,
                $cp->transformation()->isPossible ? 'Transformation' : null,
                $cp->composting()->isPossible ? 'Composting' : null,
            ]));

            $lines[] = sprintf(
                '- ID: %s | Name: %s | Stage: %s | Possible priorities: %s',
                $cp->id(),
                $cp->name(),
                $cp->supplyChainStage()->value,
                [] !== $possiblePriorities ? implode(', ', $possiblePriorities) : 'none',
            );
        }

        $lines[] = '';
        $lines[] = 'Available Actions:';

        foreach ($this->actions as $action) {
            $lines[] = sprintf(
                '- ID: %s | Description: %s | Priority: %s',
                $action->id(),
                $action->description(),
                $action->priority()->value,
            );
        }

        return implode("\n", $lines);
    }

    /** @return array<mixed> */
    public function responseSchema(): array
    {
        return [
            'type' => 'ARRAY',
            'items' => [
                'type' => 'OBJECT',
                'properties' => [
                    'criticalPointId' => ['type' => 'STRING'],
                    'actionIds' => [
                        'type' => 'ARRAY',
                        'items' => ['type' => 'STRING'],
                    ],
                ],
                'required' => ['criticalPointId', 'actionIds'],
            ],
        ];
    }

    public function temperature(): float
    {
        return $this->temperature;
    }
}
