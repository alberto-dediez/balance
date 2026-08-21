<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\AI\Gemini;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportData;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\AI\Gemini\GeminiModelTier;
use App\Shared\Infrastructure\AI\Gemini\GeminiPrompt;

final class GenerateReportTextsPrompt implements GeminiPrompt
{
    private const int MAX_FACTOR_LINES = 120;

    public function __construct(
        private readonly EventCarbonFootprintReportData $data,
        private readonly Locale $locale,
        private readonly float $temperature = 0.3,
    ) {}

    public function text(): string
    {
        $lines = [
            'You are a sustainability consultant specialized in the environmental impact of events.',
            'You are writing sections of a carbon footprint report for the following event.',
            '',
            sprintf('Event name: %s', $this->data->eventName),
            sprintf('Sector: %s', null !== $this->data->sector ? $this->data->sector->value : 'unknown'),
            sprintf('Typology: %s', null !== $this->data->typology ? $this->data->typology->value : 'unknown'),
            sprintf('Size: %s', null !== $this->data->size ? $this->data->size->value : 'unknown'),
            sprintf('Estimated attendees: %s', (string) ($this->data->estimatedAttendees ?? 'unknown')),
            sprintf('Emission factor group: %s', $this->data->emissionFactorGroupName),
            sprintf('Calculation year: %d', $this->data->calculationYear),
            '',
            sprintf('Total emissions: %s kg CO2eq, %s kg CO2, %s kg CH4, %s kg N2O', $this->data->totalEmissions->co2eq(), $this->data->totalEmissions->co2(), $this->data->totalEmissions->ch4(), $this->data->totalEmissions->n2o()),
            sprintf('Emissions per attendee: %s kg CO2eq', $this->data->emissionsPerAttendee->co2eq()),
            '',
            'Emissions by source (kg CO2eq):',
        ];

        foreach ($this->data->emissionsBySource as $source => $emissions) {
            $lines[] = sprintf('- %s: %s', $source, $emissions->co2eq());
        }

        $lines[] = '';
        $lines[] = 'Emissions by phase (kg CO2eq):';
        foreach ($this->data->emissionsByPhase as $phase => $emissions) {
            $lines[] = sprintf('- %s: %s', $phase, $emissions->co2eq());
        }

        $lines[] = '';
        $lines[] = 'Applied emission factors (item | factor kgCO2eq/unit | year | source), aggregated and capped:';
        foreach ($this->factorLines() as $factorLine) {
            $lines[] = sprintf('- %s', $factorLine);
        }

        if ([] !== $this->data->exclusionLines) {
            $lines[] = '';
            $lines[] = 'Excluded emission sources (phase | source | reason | comment):';
            foreach ($this->data->exclusionLines as $exclusionLine) {
                $lines[] = sprintf('- %s | %s | %s | %s', $exclusionLine->phase->value, $exclusionLine->source->value, $exclusionLine->reason->value, $exclusionLine->comment ?? 'n/a');
            }
        }

        $compensation = $this->data->compensation;
        $hasCompensation = null !== $compensation && $compensation->hasCompensations();

        if ($hasCompensation) {
            $lines[] = '';
            $lines[] = 'Emission compensation achieved for this event:';
            $lines[] = sprintf('- Compensated by the organizing company: %s tonnes CO2eq (%d contributions)', $this->number($compensation->ownTonnes), $compensation->ownContributions);
            $lines[] = sprintf('- Compensated by the attendees: %s tonnes CO2eq (%d contributions)', $this->number($compensation->attendeesTonnes), $compensation->attendeesContributions);
            $lines[] = sprintf('- Total compensated: %s tonnes CO2eq', $this->number($compensation->totalTonnes));
            $lines[] = sprintf('- Coverage over the total emissions of the event: %s %%', $this->number($compensation->coveragePercentage));
            $lines[] = sprintf('- Data cut-off date: %s', $compensation->cutOffAt->format('Y-m-d'));
            $lines[] = 'Projects (scope | project | location | registry id | contributions | tonnes):';

            foreach ($compensation->projectLines as $projectLine) {
                $lines[] = sprintf(
                    '- %s | %s | %s | %s | %d | %s',
                    $projectLine->scope->value,
                    $projectLine->projectName,
                    $projectLine->projectLocation ?? 'n/a',
                    $projectLine->projectRegistryId ?? 'n/a',
                    $projectLine->contributions,
                    $this->number($projectLine->tonnes),
                );
            }
        }

        $lines[] = '';
        $lines[] = $hasCompensation ? 'Write five sections:' : 'Write four sections:';
        $lines[] = '1. executiveSummary: a concise executive summary of the event carbon footprint results.';
        $lines[] = '2. methodology: an explanation of the calculation methodology used (emission factors, phases, sources).';
        $lines[] = '3. emissionFactorSources: a description of the origin/references of the emission factors applied.';
        $lines[] = '4. analysisAndRecommendations: an analysis of the results and concrete recommendations to reduce emissions.';

        if ($hasCompensation) {
            $lines[] = '5. compensation: a description of the emission compensation achieved for this event, covering the contributions of the organizing company and of the attendees, the coverage reached and the projects involved.';
            $lines[] = '';
            $lines[] = 'Rules for the compensation section:';
            $lines[] = '- Do not claim carbon neutrality or full compensation if the coverage is below 100 %.';
            $lines[] = '- Place compensation after emission reduction in the mitigation hierarchy: compensation complements reduction, it does not replace it.';
            $lines[] = '- Do not invent figures, project names or registries beyond the ones supplied above.';
        }

        if ([] !== $this->data->exclusionLines) {
            $lines[] = '';
            $lines[] = 'The excluded emission sources listed above were deliberately left out of the calculation for the given phases.';
            $lines[] = 'In the methodology section, mention these exclusions as limits of the scope of the calculation.';
            $lines[] = 'In the analysis, do not interpret a zero value for an excluded phase/source as an actual absence of emissions, and do not base reduction recommendations on excluded sources.';
        }
        $lines[] = '';
        $lines[] = 'Do not include any placeholders. Write generic but complete content, avoiding fabricated numbers not present above.';
        $lines[] = sprintf('Write all output in %s.', $this->locale->name);
        $lines[] = 'Format each section in Markdown using paragraphs, bold text and bullet lists (and ### subheadings where helpful); do not use tables, code blocks or links.';
        $lines[] = '';
        $lines[] = 'Respond strictly as a JSON object with this exact shape:';
        $lines[] = $hasCompensation
            ? '{"executiveSummary": "string", "methodology": "string", "emissionFactorSources": "string", "analysisAndRecommendations": "string", "compensation": "string"}'
            : '{"executiveSummary": "string", "methodology": "string", "emissionFactorSources": "string", "analysisAndRecommendations": "string"}';

        return implode("\n", $lines);
    }

    private function hasCompensation(): bool
    {
        $compensation = $this->data->compensation;

        return null !== $compensation && $compensation->hasCompensations();
    }

    private function number(float $value): string
    {
        return number_format($value, 3, '.', '');
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
        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'executiveSummary' => ['type' => 'STRING'],
                'methodology' => ['type' => 'STRING'],
                'emissionFactorSources' => ['type' => 'STRING'],
                'analysisAndRecommendations' => ['type' => 'STRING'],
            ],
            'required' => ['executiveSummary', 'methodology', 'emissionFactorSources', 'analysisAndRecommendations'],
        ];

        if ($this->hasCompensation()) {
            $schema['properties']['compensation'] = ['type' => 'STRING'];
            $schema['required'][] = 'compensation';
        }

        return $schema;
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
