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

        $lines[] = '';
        $lines[] = 'Write four sections:';
        $lines[] = '1. executiveSummary: a concise executive summary of the event carbon footprint results.';
        $lines[] = '2. methodology: an explanation of the calculation methodology used (emission factors, phases, sources).';
        $lines[] = '3. emissionFactorSources: a description of the origin/references of the emission factors applied.';
        $lines[] = '4. analysisAndRecommendations: an analysis of the results and concrete recommendations to reduce emissions.';
        $lines[] = '';
        $lines[] = 'Do not include any placeholders. Write generic but complete content, avoiding fabricated numbers not present above.';
        $lines[] = sprintf('Write all output in %s.', $this->locale->name);
        $lines[] = 'Format each section in Markdown using paragraphs, bold text and bullet lists (and ### subheadings where helpful); do not use tables, code blocks or links.';
        $lines[] = '';
        $lines[] = 'Respond strictly as a JSON object with this exact shape:';
        $lines[] = '{"executiveSummary": "string", "methodology": "string", "emissionFactorSources": "string", "analysisAndRecommendations": "string"}';

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
                'executiveSummary' => ['type' => 'STRING'],
                'methodology' => ['type' => 'STRING'],
                'emissionFactorSources' => ['type' => 'STRING'],
                'analysisAndRecommendations' => ['type' => 'STRING'],
            ],
            'required' => ['executiveSummary', 'methodology', 'emissionFactorSources', 'analysisAndRecommendations'],
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
