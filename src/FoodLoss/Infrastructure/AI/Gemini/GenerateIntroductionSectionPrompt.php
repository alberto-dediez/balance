<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\AI\Gemini;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\Property\Domain\Model\Property\Property;
use App\Security\Domain\Model\Account\Account;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\AI\Gemini\GeminiPrompt;

final class GenerateIntroductionSectionPrompt implements GeminiPrompt
{
    /** @param Property[] $properties */
    public function __construct(
        private readonly ?Account $account,
        private readonly PreventionPlan $plan,
        private readonly array $properties,
        private readonly \DateTimeImmutable $createdAt,
        private readonly Locale $locale = Locale::Spanish,
        private readonly float $temperature = 0.4,
    ) {}

    public function text(): string
    {
        $lines = [
            'You are an expert consultant in food safety regulations and food waste prevention in Spain.',
            '',
            'Write the "Introduction and Basic Information" section of a Food Waste Prevention Plan compliant with Spanish Law 1/2025 (Ley 1/2025 de Prevención de las Pérdidas y el Desperdicio Alimentario) for the HORECA sector.',
            '',
            sprintf('The output must be written in %s and formatted in Markdown.', $this->locale->name),
            '',
            'Available company data:',
            sprintf('- Company name: %s', $this->account?->name() ?? '[Company Name]'),
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
        $lines[] = 'Use the following placeholders for data that must be completed manually by the responsible person:';
        $lines[] = '- [Company NIF]';
        $lines[] = '- [Fiscal address of the company]';
        $lines[] = '- [Responsible person for plan implementation]';
        $lines[] = '- [Approximate number of employees]';
        $lines[] = '- [Approximate number of daily diners / guests]';
        $lines[] = '';
        $lines[] = 'The text must be formal and legal in tone. It must:';
        $lines[] = '- Provide the legal context and purpose of Law 1/2025';
        $lines[] = '- Identify the company and the scope of this plan';
        $lines[] = '- State the company\'s commitment to food waste reduction';
        $lines[] = '- List the properties covered by the plan';
        $lines[] = '';
        $lines[] = 'Use H2 headings and structured paragraphs.';

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
