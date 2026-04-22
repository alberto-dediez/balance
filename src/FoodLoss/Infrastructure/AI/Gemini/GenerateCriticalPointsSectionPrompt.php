<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\AI\Gemini;

use App\FoodLoss\Domain\Model\Action\Action;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointReasonLink;
use App\FoodLoss\Domain\Model\PreventionPlan\ProposedAction;
use App\FoodLoss\Domain\Model\Reason\Reason;
use App\Property\Domain\Model\Property\Property;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\AI\Gemini\GeminiPrompt;

final class GenerateCriticalPointsSectionPrompt implements GeminiPrompt
{
    /**
     * @param array<string, Property>        $propertyMap              indexed by property ID string
     * @param array<string, CriticalPoint[]> $criticalPointsByProperty indexed by property ID string
     * @param ProposedAction[]               $proposedActions
     * @param array<string, Action>          $actionMap                indexed by action ID string
     * @param array<string, Reason>          $reasonMap                indexed by reason ID string
     */
    public function __construct(
        private readonly array $propertyMap,
        private readonly array $criticalPointsByProperty,
        private readonly array $proposedActions,
        private readonly array $actionMap,
        private readonly array $reasonMap,
        private readonly Locale $locale = Locale::Spanish,
        private readonly float $temperature = 0.4,
    ) {}

    public function text(): string
    {
        $proposedActionsByCriticalPointId = $this->indexProposedActionsByCriticalPointId();

        $lines = [
            'You are an expert consultant in food safety regulations and food waste prevention in Spain.',
            '',
            'Write the "Critical Points Analysis and Current Situation" section of a Food Waste Prevention Plan compliant with Spanish Law 1/2025 (Ley 1/2025) for the HORECA sector.',
            '',
            sprintf('The output must be written in %s and formatted in Markdown.', $this->locale->name),
            '',
            'For each property listed below, describe:',
            '1. The critical points where food waste is generated, their supply chain stage, and which types of waste reduction measures are feasible.',
            '2. The preventive actions that are already being implemented at each critical point.',
            '3. A technical and operational justification for measures not yet in place, based on the registered reasons.',
            '',
            'Data:',
        ];

        foreach ($this->propertyMap as $propertyIdStr => $property) {
            $propertyType = $property->info()->propertyType();
            $type = null !== $propertyType ? $propertyType->value : 'Unknown';
            $lines[] = '';
            $lines[] = sprintf('### Property: %s (%s)', $property->name(), $type);

            $criticalPoints = $this->criticalPointsByProperty[$propertyIdStr] ?? [];

            if ([] === $criticalPoints) {
                $lines[] = '- No critical points registered.';
                continue;
            }

            foreach ($criticalPoints as $cp) {
                $possiblePriorities = array_values(array_filter([
                    $cp->prevention()->isPossible ? 'Prevention' : null,
                    $cp->humanDonation()->isPossible ? 'HumanDonation' : null,
                    $cp->animalFeed()->isPossible ? 'AnimalFeed' : null,
                    $cp->transformation()->isPossible ? 'Transformation' : null,
                    $cp->composting()->isPossible ? 'Composting' : null,
                ]));

                $lines[] = sprintf(
                    '- **%s** | Stage: %s | Feasible priorities: %s',
                    $cp->name(),
                    $cp->supplyChainStage()->value,
                    [] !== $possiblePriorities ? implode(', ', $possiblePriorities) : 'none',
                );

                $cpProposedActions = $proposedActionsByCriticalPointId[(string) $cp->id()] ?? [];

                if ([] !== $cpProposedActions) {
                    $lines[] = '  - Proposed preventive actions in this plan:';
                    foreach ($cpProposedActions as $pa) {
                        $action = $this->actionMap[(string) $pa->actionId()] ?? null;
                        if (null !== $action) {
                            $lines[] = sprintf('    - [%s] %s', $action->priority()->value, $action->description());
                        }
                    }
                }

                /** @var CriticalPointReasonLink[] $reasonLinks */
                $reasonLinks = $cp->reasons()->toArray();

                if ([] !== $reasonLinks) {
                    $lines[] = '  - Reasons why certain measures are not implemented:';
                    foreach ($reasonLinks as $reasonLink) {
                        $reason = $this->reasonMap[(string) $reasonLink->reasonId()] ?? null;
                        if (null !== $reason) {
                            $lines[] = sprintf('    - [%s] %s', $reasonLink->priority()->value, $reason->description());
                        }
                    }
                }
            }
        }

        $lines[] = '';
        $lines[] = 'Structure the response with an H2 section title and H3 subsections per property.';
        $lines[] = 'Use bullet points for critical points, actions, and reasons.';
        $lines[] = 'For measures not yet implemented, expand the reasons with a technical and operational justification.';

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

    /** @return array<string, ProposedAction[]> */
    private function indexProposedActionsByCriticalPointId(): array
    {
        $index = [];
        foreach ($this->proposedActions as $pa) {
            $index[(string) $pa->criticalPointId()][] = $pa;
        }

        return $index;
    }
}
