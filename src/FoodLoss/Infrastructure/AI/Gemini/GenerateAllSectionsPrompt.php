<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\AI\Gemini;

use App\FoodLoss\Domain\Model\Action\Action;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointReasonLink;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\ProposedAction;
use App\FoodLoss\Domain\Model\Reason\Reason;
use App\Property\Domain\Model\Company\Company;
use App\Property\Domain\Model\Property\Property;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\AI\Gemini\GeminiPrompt;

final class GenerateAllSectionsPrompt implements GeminiPrompt
{
    /**
     * @param Property[]                     $properties               unindexed array
     * @param array<string, Property>        $propertyMap              indexed by property ID string
     * @param array<string, CriticalPoint[]> $criticalPointsByProperty indexed by property ID string
     * @param ProposedAction[]               $proposedActions
     * @param array<string, Action>          $actionMap                indexed by action ID string
     * @param array<string, CriticalPoint>   $criticalPointMap         indexed by critical point ID string
     * @param array<string, Reason>          $reasonMap                indexed by reason ID string
     */
    public function __construct(
        private readonly PreventionPlan $plan,
        private readonly Company $company,
        private readonly array $properties,
        private readonly \DateTimeImmutable $createdAt,
        private readonly array $propertyMap,
        private readonly array $criticalPointsByProperty,
        private readonly array $proposedActions,
        private readonly array $actionMap,
        private readonly array $criticalPointMap,
        private readonly array $reasonMap,
        private readonly Locale $locale = Locale::Spanish,
        private readonly float $temperature = 0.4,
    ) {}

    public function text(): string
    {
        $proposedActionsByCriticalPointId = $this->indexProposedActionsByCriticalPointId();
        $byPriority = $this->groupByPriority();
        $donationActions = $this->resolveDonationActions();

        $lines = [
            'You are an expert consultant in food safety regulations and food waste prevention in Spain.',
            '',
            'You will generate EXACTLY 5 sections for a Food Waste Prevention Plan compliant with Spanish Law 1/2025 (Ley 1/2025 de Prevención de las Pérdidas y el Desperdicio Alimentario) for the HORECA sector.',
            '',
            sprintf('Write all output in %s and format in Markdown.', $this->locale->name),
            '',
            '## COMMON DATA (for all 5 sections):',
            '',
            'Company information:',
            sprintf('- Company name: %s', $this->company->name()),
            sprintf('- VAT number: %s', $this->company->vatNumber()),
            sprintf('- Address: %s, Postal Code %s, %s (%s)', $this->company->address()->street(), $this->company->address()->postalCode(), $this->company->address()->city(), $this->company->address()->country()),
            '',
            'Plan information:',
            sprintf('- Plan name: %s', $this->plan->name()),
            sprintf('- Plan date: %s', $this->createdAt->format('d/m/Y')),
            '',
            'Properties included in this plan:',
        ];

        foreach ($this->properties as $property) {
            $propertyType = $property->info()->propertyType();
            $type = null !== $propertyType ? $propertyType->value : 'Unknown';
            $address = $property->info()->address();
            $zone = $property->info()->zone();
            $rooms = $property->info()->rooms();
            $location = implode(', ', array_filter([$address, $zone]));
            $detail = implode(' | ', array_filter([
                $location ?: null,
                null !== $rooms && $rooms > 0 ? "{$rooms} rooms" : null,
            ]));
            $lines[] = sprintf('- %s (%s)%s', $property->name(), $type, $detail ? ": {$detail}" : '');
        }

        $lines[] = '';
        $lines[] = 'Critical points and proposed actions by property:';
        foreach ($this->propertyMap as $propertyIdStr => $property) {
            $propertyType = $property->info()->propertyType();
            $type = null !== $propertyType ? $propertyType->value : 'Unknown';
            $lines[] = '';
            $lines[] = sprintf('### %s (%s)', $property->name(), $type);

            $criticalPoints = $this->criticalPointsByProperty[$propertyIdStr] ?? [];

            if ([] === $criticalPoints) {
                $lines[] = 'No critical points registered.';
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
                    $lines[] = '  - Proposed actions:';
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
        $lines[] = '---';
        $lines[] = '';
        $lines[] = 'Now generate the 5 sections in order. Output MUST be a JSON array with exactly 5 objects, each with "title" and "content" fields. The sections are:';
        $lines[] = '';
        $lines[] = '## SECTION 1: Introduction and Basic Information';
        $lines[] = 'Write a formal, legal introduction that:';
        $lines[] = '- Provides legal context and purpose of Law 1/2025';
        $lines[] = '- Identifies the company and scope of the plan';
        $lines[] = '- States company commitment to food waste reduction';
        $lines[] = '- Lists properties covered by the plan';
        $lines[] = 'Use H2 headings and structured paragraphs. Include placeholders: [Responsible person], [Approximate number of employees], [Approximate number of daily diners/guests].';
        $lines[] = '';
        $lines[] = '## SECTION 2: Critical Points Analysis and Current Situation';
        $lines[] = 'For each property, describe:';
        $lines[] = '1. Critical points where food waste occurs, their supply chain stage, and feasible waste reduction measures';
        $lines[] = '2. Preventive actions already being implemented at each critical point';
        $lines[] = '3. Technical and operational justification for measures not yet in place, based on registered reasons';
        $lines[] = 'Use H3 subsections per property, bullet points for critical points/actions/reasons. Expand reasons with technical justification.';
        $lines[] = '';
        $lines[] = '## SECTION 3: Action Plan and Prevention Hierarchy';
        $lines[] = 'Present agreed actions following the legal five-level hierarchy:';
        $lines[] = '- Level 1 — Prevention (highest priority): prevent waste at source';
        $lines[] = '- Level 2 — Human donation: surplus safe food for consumption through donation';
        $lines[] = '- Level 3 — Animal feed: food unsuitable for human consumption as animal feed';
        $lines[] = '- Level 4 — Transformation: processing surplus into new food products';
        $lines[] = '- Level 5 — Composting: biological recovery when no higher-priority option is feasible';
        $lines[] = 'Explain proactively how each action complies with Law 1/2025.';
        $lines[] = 'Include a mandatory protocol subsection describing the obligation to offer customers recyclable food-safe containers (staff training, point-of-service communication).';
        $lines[] = '';

        $lines = array_merge($lines, $this->formatActionsForPriority($byPriority, Priority::Prevention));

        $lines[] = '';
        $lines = array_merge($lines, $this->formatActionsForPriority($byPriority, Priority::HumanDonation));

        $lines[] = '';
        $lines = array_merge($lines, $this->formatActionsForPriority($byPriority, Priority::AnimalFeed));

        $lines[] = '';
        $lines = array_merge($lines, $this->formatActionsForPriority($byPriority, Priority::Transformation));

        $lines[] = '';
        $lines = array_merge($lines, $this->formatActionsForPriority($byPriority, Priority::Composting));

        $lines[] = '';
        $lines[] = '## SECTION 4: Surplus Management and Donations';
        $lines[] = 'Document donation actions planned, receiving entities, logistics protocol, and food safety guarantees.';

        if ([] !== $donationActions) {
            $lines[] = 'Donation actions in this plan:';
            foreach ($donationActions as $item) {
                $lines[] = sprintf(
                    '- %s | Property: %s | Critical point: %s',
                    $item['action']->description(),
                    $item['property']?->name() ?? '[Unknown property]',
                    $item['criticalPoint']?->name() ?? '[Unknown critical point]',
                );
            }
        } else {
            $lines[] = 'No specific donation actions proposed yet. Write generically about establishing donation agreements.';
        }

        $lines[] = 'Include placeholders: [Receiving entity name], [Agreement date], [Collection frequency], [Cold chain responsible], [Max time between preparation and handover].';
        $lines[] = 'Cover: (1) Legal basis of Law 1/2025, (2) Receiving entities and agreements, (3) Logistics protocol, (4) Food safety and traceability guarantees.';
        $lines[] = '';
        $lines[] = '## SECTION 5: Monitoring, KPIs and Staff Training';
        $lines[] = 'Demonstrate that the plan is a living, audited document. Law 1/2025 requires measuring and reporting food waste annually and training staff.';
        $lines[] = 'Suggest specific, measurable KPIs relevant to the identified critical stages (kg waste/week, % waste by stage, plates returned uneaten, kg donated/month).';
        $lines[] = 'Include placeholders: [KPI 1 name/unit], [KPI 2 name/unit], [Plan review frequency], [Monitoring responsible], [Staff training plan], [Next review date].';
        $lines[] = 'Cover: (1) Measurement indicators (KPIs) in a table, (2) Review and reporting schedule, (3) Staff awareness and training plan.';
        $lines[] = 'Present KPIs in a table. Use H2/H3 headings.';
        $lines[] = '';
        $lines[] = 'CRITICAL: Output must be valid JSON. Return an array of exactly 5 objects:';
        $lines[] = '[';
        $lines[] = '  { "title": "Introduction and Basic Information", "content": "..." },';
        $lines[] = '  { "title": "Critical Points Analysis and Current Situation", "content": "..." },';
        $lines[] = '  { "title": "Action Plan and Prevention Hierarchy", "content": "..." },';
        $lines[] = '  { "title": "Surplus Management and Donations", "content": "..." },';
        $lines[] = '  { "title": "Monitoring, KPIs and Staff Training", "content": "..." }';
        $lines[] = ']';

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
                    'title' => ['type' => 'STRING'],
                    'content' => ['type' => 'STRING'],
                ],
                'required' => ['title', 'content'],
            ],
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
