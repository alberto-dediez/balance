<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\AI\Gemini;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\AI\Gemini\GeminiModelTier;
use App\Shared\Infrastructure\AI\Gemini\GeminiPrompt;

final class GenerateOrganizationalReportTextsPrompt implements GeminiPrompt
{
    private const int MAX_FACTOR_LINES = 120;

    public function __construct(
        private readonly OrganizationalCarbonFootprintReportData $data,
        private readonly Locale $locale,
        private readonly float $temperature = 0.3,
    ) {}

    public function text(): string
    {
        $lines = [
            'You are a sustainability consultant specialized in organizational carbon footprints calculated according to the GHG Protocol.',
            'You are writing sections of a carbon footprint report for the following organization.',
            '',
            sprintf('Subject type: %s', $this->data->subjectType->value),
            sprintf('Name: %s', $this->data->subjectName),
            sprintf('Company: %s', $this->data->companyName ?? 'n/a'),
            sprintf('Country: %s', $this->data->countryName ?? 'n/a'),
            sprintf('Emission factor group: %s', $this->data->emissionFactorGroupName),
            sprintf('Calculation year: %d', $this->data->calculationYear),
            '',
            sprintf('Total emissions: %s kg CO2eq, %s kg CO2, %s kg CH4, %s kg N2O', $this->data->totalEmissions->co2eq(), $this->data->totalEmissions->co2(), $this->data->totalEmissions->ch4(), $this->data->totalEmissions->n2o()),
            sprintf('Location-based electricity emissions: %s kg CO2eq', $this->data->locationBasedElectricityEmissions->co2eq()),
            '',
            'Emissions by scope (kg CO2eq):',
        ];

        foreach ($this->data->emissionsByScope as $scope => $emissions) {
            $lines[] = sprintf('- %s: %s', $scope, $emissions->co2eq());
        }

        $lines[] = '';
        $lines[] = 'Emissions by source (kg CO2eq):';
        foreach ($this->data->emissionsBySource as $source => $emissions) {
            $lines[] = sprintf('- %s: %s', $source, $emissions->co2eq());
        }

        $lines[] = '';
        $lines[] = 'Emissions by scope and source (kg CO2eq):';
        foreach ($this->data->emissionsByScopeAndSource as $scope => $sources) {
            foreach ($sources as $source => $emissions) {
                $lines[] = sprintf('- %s | %s: %s', $scope, $source, $emissions->co2eq());
            }
        }

        if ([] !== $this->data->emissionsByProperty) {
            $lines[] = '';
            $lines[] = 'Emissions by property (kg CO2eq):';
            foreach ($this->data->emissionsByProperty as $row) {
                $lines[] = sprintf('- %s: %s', $row['name'], $row['emissions']->co2eq());
            }
        }

        $lines[] = '';
        $lines[] = 'Applied emission factors (item | factor kgCO2eq/unit | year | source), aggregated and capped:';
        foreach ($this->factorLines() as $factorLine) {
            $lines[] = sprintf('- %s', $factorLine);
        }

        if ([] !== $this->data->exclusionLines) {
            $lines[] = '';
            $lines[] = 'Excluded emission sources (scope | source | reason | comment):';
            foreach ($this->data->exclusionLines as $exclusionLine) {
                $lines[] = sprintf('- %s | %s | %s | %s', $exclusionLine->scope->value, $exclusionLine->source->value, $exclusionLine->reason->value, $exclusionLine->comment ?? 'n/a');
            }
        }

        $lines[] = '';
        $lines[] = 'Write four sections:';
        $lines[] = '1. purpose: the purpose of the report, presenting the carbon footprint as an environmental indicator of the activity and as the first step to reduce emissions, with a reference to the carbon footprint registry of the Spanish Ministry for the Ecological Transition (MITECO).';
        $lines[] = '2. regulatoryContext: the regulatory context: the GHG Protocol and its five principles (completeness, consistency, transparency, relevance and accuracy), the scope 1 and scope 2 boundaries, ISO 14064-1 as a reference and the MITECO carbon footprint registry; describe the limits of the calculation including the excluded sources.';
        $lines[] = '3. sustainableDevelopmentGoals: the relation of the footprint with the Sustainable Development Goals 13 (climate action), 7 (affordable and clean energy) and 12 (responsible consumption and production), applied to the activity of the subject.';
        $lines[] = '4. conclusion: the main results with the figures supplied above, the most relevant sources and concrete reduction lines, without relying on excluded sources.';

        if ([] !== $this->data->exclusionLines) {
            $lines[] = '';
            $lines[] = 'The excluded emission sources listed above were deliberately left out of the calculation for the given scopes.';
            $lines[] = 'In the analysis, do not interpret a zero value for an excluded scope/source as an actual absence of emissions, and do not base reduction recommendations on excluded sources.';
        }

        $lines[] = '';
        $lines[] = 'Do not include any placeholders. Write generic but complete content, avoiding fabricated numbers not present above.';
        $lines[] = sprintf('Write all output in %s.', $this->locale->name);
        $lines[] = 'Format each section in Markdown using paragraphs, bold text and bullet lists (and ### subheadings where helpful); do not use tables, code blocks or links.';
        $lines[] = '';
        $lines[] = 'Respond strictly as a JSON object with this exact shape:';
        $lines[] = '{"purpose": "string", "regulatoryContext": "string", "sustainableDevelopmentGoals": "string", "conclusion": "string"}';

        return implode("\n", $lines);
    }

    /** @return list<string> */
    private function factorLines(): array
    {
        $seen = [];
        $lines = [];

        foreach ($this->data->activityLines as $activityLine) {
            $key = $activityLine->itemName . '|' . $activityLine->factorCo2eq . '|' . $activityLine->factorYear . '|' . ($activityLine->factorSource ?? '');

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $lines[] = sprintf('%s | %s | %d | %s', $activityLine->itemName, $activityLine->factorCo2eq, $activityLine->factorYear, $activityLine->factorSource ?? 'n/a');

            if (count($lines) >= self::MAX_FACTOR_LINES) {
                break;
            }
        }

        return $lines;
    }

    public function responseSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'purpose' => ['type' => 'STRING'],
                'regulatoryContext' => ['type' => 'STRING'],
                'sustainableDevelopmentGoals' => ['type' => 'STRING'],
                'conclusion' => ['type' => 'STRING'],
            ],
            'required' => ['purpose', 'regulatoryContext', 'sustainableDevelopmentGoals', 'conclusion'],
        ];
    }

    public function temperature(): float
    {
        return $this->temperature;
    }

    public function modelTier(): GeminiModelTier
    {
        return GeminiModelTier::MEDIUM;
    }
}
