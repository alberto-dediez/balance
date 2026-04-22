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

final class GenerateDonationsSectionPrompt implements GeminiPrompt
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
        $donationActions = $this->resolveDonationActions();

        $lines = [
            'You are an expert consultant in food safety regulations and food waste prevention in Spain.',
            '',
            'Write the "Surplus Management and Donations" section of a Food Waste Prevention Plan compliant with Spanish Law 1/2025 (Ley 1/2025) for the HORECA sector.',
            '',
            sprintf('The output must be written in %s and formatted in Markdown.', $this->locale->name),
            '',
            'Law 1/2025 establishes human consumption as the highest-priority destination for surplus food that is still safe to eat.',
            'This section must document: the donation actions planned, the receiving entities, the logistics protocol, and the food safety guarantees.',
            '',
        ];

        if ([] !== $donationActions) {
            $lines[] = 'Donation actions planned in this prevention plan:';
            foreach ($donationActions as $item) {
                $lines[] = sprintf(
                    '- %s | Property: %s | Critical point: %s',
                    $item['action']->description(),
                    $item['property']?->name() ?? '[Unknown property]',
                    $item['criticalPoint']?->name() ?? '[Unknown critical point]',
                );
            }
        } else {
            $lines[] = 'No specific donation actions have been proposed yet in this plan.';
            $lines[] = 'Write a generic section explaining the legal obligation and recommending how to establish donation agreements.';
        }

        $lines[] = '';
        $lines[] = 'Use the following placeholders for data that must be completed manually:';
        $lines[] = '- [Name of receiving entity (Food Bank, NGO, social canteen)]';
        $lines[] = '- [Date of donation agreement signature]';
        $lines[] = '- [Collection frequency: daily / weekly / as needed]';
        $lines[] = '- [Person responsible for cold chain during handover]';
        $lines[] = '- [Maximum time between preparation and handover]';
        $lines[] = '';
        $lines[] = 'The section must cover:';
        $lines[] = '1. Legal basis and priority of human consumption under Law 1/2025';
        $lines[] = '2. Receiving entities and collaboration agreements';
        $lines[] = '3. Logistics protocol: who collects, how often, cold chain responsibilities';
        $lines[] = '4. Food safety and traceability guarantees during handover';
        $lines[] = '';
        $lines[] = 'Use H2/H3 headings and structured bullet points.';

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

    /** @return list<array{action: Action, property: ?Property, criticalPoint: ?CriticalPoint}> */
    private function resolveDonationActions(): array
    {
        $result = [];

        foreach ($this->proposedActions as $pa) {
            $action = $this->actionMap[(string) $pa->actionId()] ?? null;
            if (null === $action || Priority::HumanDonation !== $action->priority()) {
                continue;
            }

            $result[] = [
                'action' => $action,
                'property' => $this->propertyMap[(string) $pa->propertyId()] ?? null,
                'criticalPoint' => $this->criticalPointMap[(string) $pa->criticalPointId()] ?? null,
            ];
        }

        return $result;
    }
}
