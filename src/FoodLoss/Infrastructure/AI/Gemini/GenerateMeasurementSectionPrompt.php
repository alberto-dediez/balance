<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\AI\Gemini;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\Property\Domain\Model\Property\Property;
use App\Security\Domain\Model\Account\Account;
use App\Shared\Infrastructure\AI\Gemini\GeminiPrompt;

final class GenerateMeasurementSectionPrompt implements GeminiPrompt
{
    /**
     * @param array<string, Property>        $propertyMap              indexed by property ID string
     * @param array<string, CriticalPoint[]> $criticalPointsByProperty indexed by property ID string
     */
    public function __construct(
        private readonly ?Account $account,
        private readonly array $propertyMap,
        private readonly array $criticalPointsByProperty,
        private readonly float $temperature = 0.4,
    ) {}

    public function text(): string
    {
        $lines = [
            'You are an expert consultant in food safety regulations and food waste prevention in Spain.',
            '',
            'Write the "Monitoring, KPIs and Staff Training" section of a Food Waste Prevention Plan compliant with Spanish Law 1/2025 (Ley 1/2025) for the HORECA sector.',
            '',
            'The output must be written in English and formatted in Markdown.',
            '',
            'Law 1/2025 requires companies to: (1) measure and report food waste annually, and (2) train staff to prevent it.',
            'This section must demonstrate to authorities that the plan is a living, audited document.',
            '',
            sprintf('Company: %s', $this->account?->name() ?? '[Company Name]'),
            '',
            'Properties and their critical supply chain stages:',
        ];

        foreach ($this->propertyMap as $propertyIdStr => $property) {
            $propertyType = $property->info()->propertyType();
            $type = null !== $propertyType ? $propertyType->value : 'Unknown';
            $lines[] = sprintf('- %s (%s)', $property->name(), $type);

            $criticalPoints = $this->criticalPointsByProperty[$propertyIdStr] ?? [];

            $stageNames = [];
            foreach ($criticalPoints as $cp) {
                $stageNames[$cp->supplyChainStage()->value] = true;
            }

            if ([] !== $stageNames) {
                $lines[] = sprintf('  - Critical stages: %s', implode(', ', array_keys($stageNames)));
            }
        }

        $lines[] = '';
        $lines[] = 'Based on the critical stages identified above, suggest specific and measurable KPIs relevant to these types of HORECA establishments.';
        $lines[] = 'Examples: kg of food discarded per week per property, % of waste by supply chain stage, number of plates returned uneaten per service, kg donated per month.';
        $lines[] = '';
        $lines[] = 'Use the following placeholders for data that must be completed manually:';
        $lines[] = '- [KPI 1: specific name and measurement unit]';
        $lines[] = '- [KPI 2: specific name and measurement unit]';
        $lines[] = '- [Plan review frequency: quarterly / annual]';
        $lines[] = '- [Person responsible for monitoring]';
        $lines[] = '- [Staff training plan: courses, workshops, frequency]';
        $lines[] = '- [Date of next scheduled plan review]';
        $lines[] = '';
        $lines[] = 'The section must cover:';
        $lines[] = '1. Measurement indicators (KPIs) with suggested values based on the identified stages';
        $lines[] = '2. Review and reporting schedule (Law 1/2025 requires annual reporting to authorities)';
        $lines[] = '3. Staff awareness and training plan for kitchen and front-of-house teams';
        $lines[] = '';
        $lines[] = 'Present KPIs in a table. Use H2/H3 headings.';

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
}
