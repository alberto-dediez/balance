<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\AI\Prompt;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\AI\AiModelTier;
use App\Shared\Infrastructure\AI\AiPrompt;
use App\Shared\Infrastructure\AI\AiThinkingLevel;

final class GenerateOrganizationalReportTextsPrompt implements AiPrompt
{
    private const int MAX_FACTOR_LINES = 120;

    public function __construct(
        private readonly OrganizationalCarbonFootprintReportData $data,
        private readonly Locale $locale,
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
            sprintf('Location-based total emissions: %s kg CO2eq', $this->data->locationBasedTotalEmissions->co2eq()),
            sprintf('Electricity activities without location-based factor: %d', $this->data->electricityActivitiesWithoutLocationBased),
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
                $lines[] = sprintf('- %s | %s | %s | %s', $exclusionLine->scope->value, $exclusionLine->source->value ?? '(whole scope)', $exclusionLine->reason->value, $exclusionLine->comment ?? 'n/a');
            }
        }

        $mitigationPlan = $this->data->mitigationPlan;

        if (null !== $mitigationPlan) {
            $lines[] = '';
            $lines[] = 'Mitigation plan (reduction and mitigation measures the organization commits to):';
            foreach ($mitigationPlan->actions as $action) {
                $lines[] = sprintf('- Measure: %s | base year %d | target year %d', $action->name, $action->baseYear, $action->targetYear);

                if ('' !== trim($action->description)) {
                    $lines[] = sprintf('  Description: %s', str_replace("\n", ' ', trim($action->description)));
                }

                foreach ($action->effects as $effect) {
                    $lines[] = sprintf('  Effect: %s | %s | %s | %s %% expected reduction at the target year', $effect['scope']->value, $effect['source']->value, $effect['itemName'], number_format($effect['reduction'] * 100, 2, '.', ''));
                }
            }

            $lines[] = '';
            $lines[] = 'Expected total emissions by year (kg CO2eq):';
            foreach ($mitigationPlan->projection->totalByYear as $year => $total) {
                $lines[] = sprintf('- %d: %s', $year, number_format($total, 2, '.', ''));
            }
        }

        $lines[] = '';
        $lines[] = null !== $mitigationPlan ? 'Write five sections:' : 'Write four sections:';
        $lines[] = '1. purpose: the purpose of the report, presenting the carbon footprint as an environmental indicator of the activity and as the first step to reduce emissions, with a reference to the carbon footprint registry of the Spanish Ministry for the Ecological Transition (MITECO).';
        $lines[] = sprintf('2. regulatoryContext: the regulatory context: the GHG Protocol and its five principles (completeness, consistency, transparency, relevance and accuracy), %s, ISO 14064-1 as a reference and the MITECO carbon footprint registry; describe the limits of the calculation including the excluded sources.', $this->boundaries());
        $lines[] = '3. sustainableDevelopmentGoals: the relation of the footprint with the Sustainable Development Goals 13 (climate action), 7 (affordable and clean energy) and 12 (responsible consumption and production), applied to the activity of the subject.';
        $lines[] = '4. conclusion: the main results with the figures supplied above, the most relevant sources and concrete reduction lines, without relying on excluded sources.';

        if (null !== $mitigationPlan) {
            $lines[] = '5. mitigationPlan: present the improvement plan and its goal; list ALL the measures of the mitigation plan in a bullet list, each one with its exact name as supplied, its target year and the sources it reduces; explain that the projection assumes the activity level remains every year at the level of the calculated year and that the reductions come from an improvement in efficiency; do not invent figures nor measures.';
        }

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
        $lines[] = null !== $mitigationPlan
            ? '{"purpose": "string", "regulatoryContext": "string", "sustainableDevelopmentGoals": "string", "conclusion": "string", "mitigationPlan": "string"}'
            : '{"purpose": "string", "regulatoryContext": "string", "sustainableDevelopmentGoals": "string", "conclusion": "string"}';

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
        $properties = [
            'purpose' => ['type' => 'STRING'],
            'regulatoryContext' => ['type' => 'STRING'],
            'sustainableDevelopmentGoals' => ['type' => 'STRING'],
            'conclusion' => ['type' => 'STRING'],
        ];

        if (null !== $this->data->mitigationPlan) {
            $properties['mitigationPlan'] = ['type' => 'STRING'];
        }

        return [
            'type' => 'OBJECT',
            'properties' => $properties,
            'required' => ['purpose', 'regulatoryContext', 'sustainableDevelopmentGoals', 'conclusion'],
        ];
    }

    public function thinkingLevel(): AiThinkingLevel
    {
        return AiThinkingLevel::MEDIUM;
    }

    public function modelTier(): AiModelTier
    {
        return AiModelTier::MEDIUM;
    }

    private function boundaries(): string
    {
        if (!$this->data->scope3Excluded) {
            return 'the scope 1, scope 2 and scope 3 boundaries';
        }

        return sprintf('the scope 1 and scope 2 boundaries (scope 3 excluded: %s)', $this->data->scope3ExclusionReason->value ?? 'n/a');
    }
}
