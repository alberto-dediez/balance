<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\AI\Prompt;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CalculationMethod;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAnnexJustification;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAnnexTopicLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAssessmentSnapshotAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportBoundaryLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportMaterialityAnnex;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
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
            sprintf('Global warming potentials of the emission factor group: CO2 1, CH4 %s, N2O %s', $this->data->gwpCh4, $this->data->gwpN2O),
            sprintf('Calculation year: %d', $this->data->calculationYear),
            'Consolidation approach: operational control (franchised and leased-out properties are reported in scope 3 categories 14 and 13)',
            sprintf('Operating regime: %s', $this->data->operatingRegime->value ?? 'n/a'),
            '',
            sprintf('Total emissions: %s kg CO2eq, %s kg CO2, %s kg CH4, %s kg N2O', $this->data->totalEmissions->co2eq(), $this->data->totalEmissions->co2(), $this->data->totalEmissions->ch4InKilograms(), $this->data->totalEmissions->n2oInKilograms()),
            sprintf('Location-based electricity emissions: %s kg CO2eq', $this->data->locationBasedElectricityEmissions->co2eq()),
        ];

        if (!$this->data->precalculated) {
            $lines[] = sprintf('Location-based total emissions: %s kg CO2eq', $this->data->locationBasedTotalEmissions->co2eq());
        }

        $lines[] = sprintf('Electricity activities without location-based factor: %d', $this->data->electricityActivitiesWithoutLocationBased);

        if ($this->data->hasCo2eqOnlyActivities()) {
            $lines[] = 'Refrigerant gas leaks and emissions reported by the organization or its suppliers are accounted only in CO2eq: their gas breakdown is not available and they are added to CO2.';
        }

        $lines[] = $this->baseYearLine();
        $lines[] = '';
        $lines[] = 'Emissions by scope (kg CO2eq):';

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

        if ([] !== $this->data->boundaryLines) {
            $lines[] = '';
            $lines[] = 'Organizational boundary (property | operating regime | treatment):';
            foreach ($this->data->boundaryLines as $boundaryLine) {
                $lines[] = sprintf('- %s | %s | %s', $boundaryLine->propertyName, $boundaryLine->regime->value, $this->boundaryTreatment($boundaryLine));
            }
        }

        if (CarbonFootprintReportSubjectType::Property === $this->data->subjectType && !$this->data->scope3Excluded) {
            $lines[] = '';
            $lines[] = sprintf(
                'Scope 3 categories %s are not applicable to a property: they are reported in the footprint of the company (leased-out or franchised properties)',
                implode(' and ', array_map(static fn(Source $source): string => (string) $source->ghgProtocolCategory(), Scope::Scope3->companyOnlySources())),
            );
        }

        $lines[] = '';
        $lines[] = 'Calculation methods by category (scope | source | GHG Protocol category | method | kg CO2eq | data quality):';
        foreach ($this->methodLines() as $methodLine) {
            $lines[] = sprintf('- %s', $methodLine);
        }

        if (!$this->data->scope3Excluded) {
            $lines[] = '';

            if ([] === $this->data->providerLines) {
                $lines[] = 'Provider contributions: none registered';
            } else {
                $lines[] = 'Provider contributions (provider | property | categories | mode | status | answered at | kg CO2eq; ignored contributions are not accounted):';
                foreach ($this->data->providerLines as $providerLine) {
                    $lines[] = sprintf(
                        '- %s | %s | %s | %s | %s | %s | %s',
                        $providerLine->providerName,
                        $providerLine->propertyName ?? 'n/a',
                        implode(', ', array_map(static fn(Source $source): string => $source->value, $providerLine->sources)),
                        $providerLine->mode->value,
                        $providerLine->status->value,
                        $providerLine->answeredAt?->format('Y-m-d') ?? 'n/a',
                        $providerLine->emissionsCo2eq,
                    );
                }
            }

            $supplierDataShare = $this->data->supplierDataSharePercent();
            $lines[] = sprintf('Supplier data share of scope 3: %s', null === $supplierDataShare ? 'n/a' : number_format($supplierDataShare, 2, '.', '') . ' %');
        }

        $lines[] = '';
        $lines[] = 'Emission factor sources (source | factor years | activity lines | kg CO2eq):';
        foreach ($this->factorSourceLines() as $factorSourceLine) {
            $lines[] = sprintf('- %s', $factorSourceLine);
        }

        $employeeSurvey = $this->data->employeeSurvey;
        $lines[] = '';
        $lines[] = null === $employeeSurvey
            ? 'Employee survey: no survey data'
            : sprintf(
                'Employee survey: %s employees, %s answers, participation rate %s, confidence level %s, margin of error %s',
                $employeeSurvey->totalEmployees ?? 'n/a',
                $employeeSurvey->answeredCount ?? 'n/a',
                null !== $employeeSurvey->participationPercent() ? number_format($employeeSurvey->participationPercent(), 2, '.', '') . ' %' : 'n/a',
                null !== $employeeSurvey->confidenceLevel ? $employeeSurvey->confidenceLevel->value . ' %' : 'n/a',
                null !== $employeeSurvey->marginOfError ? number_format($employeeSurvey->marginOfError * 100, 2, '.', '') . ' %' : 'n/a',
            );

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

        $lines[] = '';
        foreach ($this->materialityLines() as $materialityLine) {
            $lines[] = $materialityLine;
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

        $hasMaterialityAnalysis = null !== $this->data->materialityAnnex && !$this->data->materialityAnnex->isAnalysisMissing() && !$this->data->materialityAnnex->hasNoScope3Topics();
        $appliesMaterialityAnalysis = $hasMaterialityAnalysis && !$this->data->scope3Excluded;
        $requestsMaterialityAnalysis = $this->requestsMaterialityAnalysis();
        $sectionCount = 7 + (null !== $mitigationPlan ? 1 : 0) + ($requestsMaterialityAnalysis ? 1 : 0);

        $lines[] = '';
        $lines[] = sprintf('Write %s sections:', match ($sectionCount) {
            7 => 'seven',
            8 => 'eight',
            default => 'nine',
        });
        $lines[] = '1. purpose: the purpose of the report, presenting the carbon footprint as an environmental indicator of the activity and as the first step to reduce emissions, with a reference to the carbon footprint registry of the Spanish Ministry for the Ecological Transition (MITECO).';
        $lines[] = sprintf(
            '2. regulatoryContext: the regulatory context: the GHG Protocol and its five principles (completeness, consistency, transparency, relevance and accuracy), %s, ISO 14064-1 as a reference and the MITECO carbon footprint registry; describe the limits of the calculation including the excluded sources; %s; never add reasons that are not listed.',
            $this->boundaries(),
            match (true) {
                $this->data->scope3Excluded => 'state that scope 3 was excluded in full with the declared reason and that the materiality recommendations are not applied',
                $hasMaterialityAnalysis => 'state that scope 3 exclusions follow the materiality assessment and cite its threshold and the verdicts above verbatim',
                null !== $this->data->materialityAnnex && $this->data->materialityAnnex->hasNoScope3Topics() => 'state that the materiality assessment was completed without scope 3 categories and that scope 3 exclusions rely on the reason and comment declared for each excluded source',
                null !== $this->data->materialityAnnex && $this->data->materialityAnnex->isCompletedWithoutVerdicts() => 'state that the materiality assessment was completed before the automatic verdicts were activated and that scope 3 exclusions rely on the reason and comment declared for each excluded source',
                default => 'state that no materiality assessment was performed and exclusions rely on the declared reasons',
            },
        );
        $lines[] = '3. methodology: the calculation methodology: the GHG Protocol Corporate Standard and Scope 3 Standard, the operational control consolidation approach, the formula activity data multiplied by emission factor, the global warming potentials supplied above, the market-based and location-based methods for scope 2, the well-to-tank emissions of category 3, the minimum boundary of categories 13 and 14, the calculation methods of the GHG Protocol Scope 3 Standard applied in each category (supplier-specific, spend-based, distance-based, fuel-based, waste-type-specific, average-data) against the activity data of scopes 1 and 2, the emissions reported by suppliers or by the organization and the extrapolation of the employee survey; mention only the methods that appear in the calculation methods listed above and do not invent figures'
            . ($appliesMaterialityAnalysis ? '; describe the materiality assessment only with the criteria, scale, threshold and rule listed above.' : '.');
        $lines[] = '4. emissionFactorSources: comment on the emission factor sources listed above, their years, the emission factor group and its global warming potentials, without inventing sources nor figures.';
        $lines[] = '5. uncertainty: comment on the data quality by category listed above, the margin of error and confidence level of the employee survey when supplied, and the limitations of the spend-based and average-data methods against supplier-specific, distance-based and fuel-based data; recommend improvements in the data collection without new figures'
            . ($appliesMaterialityAnalysis ? '; mention manual overrides and pending reviews of the materiality assessment exactly as listed.' : '.');
        $lines[] = '6. sustainableDevelopmentGoals: the relation of the footprint with the Sustainable Development Goals 13 (climate action), 7 (affordable and clean energy) and 12 (responsible consumption and production), applied to the activity of the subject.';
        $lines[] = '7. conclusion: the main results with the figures supplied above, the most relevant sources and concrete reduction lines, without relying on excluded sources.';

        if (null !== $mitigationPlan) {
            $lines[] = '8. mitigationPlan: present the improvement plan and its goal; list ALL the measures of the mitigation plan in a bullet list, each one with its exact name as supplied, its target year and the sources it reduces; explain that the projection assumes the activity level remains every year at the level of the calculated year and that the reductions come from an improvement in efficiency; do not invent figures nor measures.';
        }

        if ($requestsMaterialityAnalysis) {
            $lines[] = sprintf(
                '%d. materialityAnalysis: explain the scope 3 materiality assessment for a verifier: one opening paragraph with the organization context and the method (criteria, scale, threshold, high-magnitude rule, as listed); then one ### heading per excluded category, exactly as "Cat. N · Name", %s; quote relevance, threshold, points and verdict verbatim; mention manual overrides exactly as listed; close with a paragraph on the consequences for the inventory and when the category should be reassessed. Do not add facts, answers or figures that are not listed; do not change or question any verdict.%s',
                null !== $mitigationPlan ? 9 : 8,
                true === $this->data->materialityAnnex?->isReopened()
                    ? 'with one or two paragraphs that explain the category only with its relevance, threshold, verdict and manual override as listed'
                    : 'with one or two paragraphs that connect the answers listed for that category with the activity of the organization and explain why its relevance is below the threshold',
                true === $this->data->materialityAnnex?->isReopened() ? ' The answers are not available: do not describe, assume or infer any answer.' : '',
            );
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
        $lines[] = sprintf(
            '{"purpose": "string", "regulatoryContext": "string", "methodology": "string", "emissionFactorSources": "string", "uncertainty": "string", "sustainableDevelopmentGoals": "string", "conclusion": "string"%s%s}',
            null !== $mitigationPlan ? ', "mitigationPlan": "string"' : '',
            $requestsMaterialityAnalysis ? ', "materialityAnalysis": "string"' : '',
        );

        return implode("\n", $lines);
    }

    /** @return list<string> */
    private function materialityLines(): array
    {
        $annex = $this->data->materialityAnnex;

        if ($this->data->scope3Excluded) {
            $lines = [sprintf(
                'Scope 3 was excluded in full: %s. %s. The recommendations of the materiality assessment are not applied.',
                $this->data->scope3ExclusionReason->value ?? 'n/a',
                $this->data->scope3ExclusionComment ?? 'n/a',
            )];

            return null !== $annex && $annex->hasDecisions ? [...$lines, ...$this->materialityVerdictLines($annex)] : $lines;
        }

        if (null !== $annex && $annex->hasNoScope3Topics()) {
            return ['The materiality assessment was completed without scope 3 categories; scope 3 exclusions rely on the reason and comment declared for each excluded source. Do not describe or invent verdicts.'];
        }

        if (null !== $annex && $annex->isCompletedWithoutVerdicts()) {
            return ['The materiality assessment was completed before the automatic verdicts were activated; scope 3 exclusions rely on the reason and comment declared for each excluded source. Do not describe or invent verdicts.'];
        }

        if (null === $annex || $annex->isAnalysisMissing()) {
            return ['No materiality assessment was performed for this footprint; scope 3 exclusions rely only on the declared reason and comment. Do not describe or invent a materiality analysis.'];
        }

        $lines = $this->materialityVerdictLines($annex);

        if ($annex->isReopened()) {
            array_unshift($lines, 'The materiality assessment was reopened and has not been completed again; the verdicts below are the ones applied to the footprint and the current answers are not available.');
        }

        return $lines;
    }

    /** @return list<string> */
    private function materialityVerdictLines(OrganizationalCarbonFootprintReportMaterialityAnnex $annex): array
    {
        $assessment = $annex->assessment;
        $criteria = array_values(array_unique(array_map(static fn(MaterialityAssessmentSnapshotAnswer $answer): string => $answer->criterionKey, $assessment->answers ?? [])));
        $lines = [
            $this->requestsMaterialityAnalysis()
                ? 'Scope 3 materiality assessment (system verdicts; cite them and never produce your own verdicts; only the materialityAnalysis section may explain them, using exclusively the answers listed):'
                : 'Scope 3 materiality assessment (system verdicts; cite them, do not produce your own justification):',
            sprintf(
                'Threshold: %s | high-magnitude rule: %s | completed at: %s | completed by: %s | sector: %s | question set version: %s | criteria: %s | scale: %s',
                null !== $assessment?->thresholdPercent ? $assessment->thresholdPercent . ' %' : 'n/a',
                null === $assessment?->highMagnitudeRule ? 'n/a' : ($assessment->highMagnitudeRule ? 'on' : 'off'),
                $assessment?->completedAt?->format('Y-m-d') ?? 'n/a',
                $assessment->completedByName ?? 'n/a',
                $assessment->sectorName ?? 'n/a',
                $assessment?->questionSetVersion?->format('Y-m-d') ?? 'n/a',
                [] === $criteria ? 'n/a' : implode(', ', $criteria),
                'low/medium/high = 1/2/3; no/yes = 1/3; normalized relevance 0–100 %',
            ),
        ];

        foreach ($annex->topics as $topic) {
            $lines[] = sprintf(
                '- Cat. %s %s | relevance %s | threshold %s | %s | forced: %s | %s | override: %s',
                $topic->number ?? 'n/a',
                $topic->name ?? $topic->source->value ?? 'n/a',
                null !== $topic->scorePercent ? number_format($topic->scorePercent, 2, '.', '') . ' %' : 'n/a',
                null !== $topic->thresholdPercent ? $topic->thresholdPercent . ' %' : 'n/a',
                $topic->verdict->value ?? 'no verdict',
                $topic->forcedByHighMagnitude ? 'yes' : 'no',
                $topic->state->value,
                $this->overrideOf($topic),
            );

            $justification = $this->justificationOf($annex, $topic);

            if (null === $justification || [] === $justification->answers) {
                continue;
            }

            foreach ($justification->answers as $answer) {
                $lines[] = sprintf(
                    '  Answer: %s | "%s" | %s | %s × %d = %d',
                    $answer->criterionKey,
                    $answer->text,
                    '' === $answer->answerLabelKey ? 'n/a' : $answer->answerLabelKey,
                    $answer->value ?? 'n/a',
                    $answer->weight,
                    $answer->points,
                );
            }

            if (null !== $justification->points) {
                $lines[] = sprintf('  Total: %d points in range %s–%s', $justification->points, $justification->minPoints ?? 'n/a', $justification->maxPoints ?? 'n/a');
            }
        }

        return $lines;
    }

    private function justificationOf(OrganizationalCarbonFootprintReportMaterialityAnnex $annex, MaterialityAnnexTopicLine $topic): ?MaterialityAnnexJustification
    {
        foreach ($annex->justifications as $justification) {
            if ($justification->topic === $topic) {
                return $justification;
            }
        }

        return null;
    }

    private function requestsMaterialityAnalysis(): bool
    {
        return null !== $this->data->materialityAnnex && $this->data->materialityAnnex->requestsNarrative($this->data->scope3Excluded);
    }

    private function overrideOf(MaterialityAnnexTopicLine $topic): string
    {
        if (null === $topic->overriddenByName) {
            return 'none';
        }

        return sprintf('%s, %s, "%s"', $topic->overriddenByName, $topic->overriddenAt?->format('Y-m-d') ?? 'n/a', $topic->overrideJustification ?? '');
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

    /** @return list<string> */
    private function methodLines(): array
    {
        $lines = [];

        foreach ($this->data->categoryMethodEmissions() as [$scope, $source, $methods]) {
            foreach ($methods as $method => $total) {
                $calculationMethod = CalculationMethod::from($method);

                $lines[] = sprintf('%s | %s | %s | %s | %s | %s', $scope->value, $source->value, $source->ghgProtocolCategory() ?? 'n/a', $calculationMethod->value, number_format($total, 2, '.', ''), $calculationMethod->dataQuality()->value);
            }
        }

        return $lines;
    }

    /** @return list<string> */
    private function factorSourceLines(): array
    {
        $lines = [];

        foreach ($this->data->emissionFactorSourceSummary() as $row) {
            $label = match (true) {
                null !== $row['method'] && null !== $row['source'] => sprintf('%s (%s)', $row['method']->value, $row['source']),
                null !== $row['method'] => $row['method']->value,
                default => $row['source'] ?? 'n/a',
            };

            $lines[] = sprintf('%s | %s | %d | %s', $label, [] === $row['years'] ? 'n/a' : implode(', ', $row['years']), $row['lines'], number_format($row['emissions'], 2, '.', ''));
        }

        return $lines;
    }

    private function baseYearLine(): string
    {
        $baseYear = $this->data->baseYear;

        if (null === $baseYear) {
            return 'Base year: not declared';
        }

        $variation = $this->data->baseYearVariationPercent();

        return sprintf(
            'Base year: %d | base-year total %s kg CO2eq | recalculation threshold %s %% | variation %s | exceeds: %s | justification: %s',
            $baseYear->year,
            $baseYear->baseYearTotalKgCo2eq,
            $baseYear->recalculationThresholdPercent,
            match (true) {
                $this->data->isBaseYear() => 'this footprint is the base year',
                null === $variation => 'n/a',
                default => sprintf('%+.2f %%', $variation),
            },
            $this->data->baseYearExceedsThreshold() ? 'yes' : 'no',
            str_replace("\n", ' ', trim($baseYear->justification)),
        );
    }

    private function boundaryTreatment(OrganizationalCarbonFootprintReportBoundaryLine $boundaryLine): string
    {
        if ($boundaryLine->consolidated) {
            return 'consolidated in scopes 1 and 2';
        }

        $source = $this->data->boundaryTreatment($boundaryLine);

        return null === $source ? 'outside the calculation (excluded)' : sprintf('scope 3 category %d', $source->ghgProtocolCategory());
    }

    public function responseSchema(): array
    {
        $properties = [
            'purpose' => ['type' => 'STRING'],
            'regulatoryContext' => ['type' => 'STRING'],
            'methodology' => ['type' => 'STRING'],
            'emissionFactorSources' => ['type' => 'STRING'],
            'uncertainty' => ['type' => 'STRING'],
            'sustainableDevelopmentGoals' => ['type' => 'STRING'],
            'conclusion' => ['type' => 'STRING'],
        ];

        if (null !== $this->data->mitigationPlan) {
            $properties['mitigationPlan'] = ['type' => 'STRING'];
        }

        if ($this->requestsMaterialityAnalysis()) {
            $properties['materialityAnalysis'] = ['type' => 'STRING'];
        }

        return [
            'type' => 'OBJECT',
            'properties' => $properties,
            'required' => ['purpose', 'regulatoryContext', 'methodology', 'emissionFactorSources', 'uncertainty', 'sustainableDevelopmentGoals', 'conclusion'],
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
        $boundaries = $this->scopeBoundaries();

        if (!$this->data->precalculated) {
            return $boundaries;
        }

        return $boundaries . '; scope 1 and scope 2 figures were provided by the organization as already calculated results, without activity breakdown';
    }

    private function scopeBoundaries(): string
    {
        if (!$this->data->scope3Excluded) {
            return 'the scope 1, scope 2 and scope 3 boundaries';
        }

        return sprintf('the scope 1 and scope 2 boundaries (scope 3 excluded: %s)', $this->data->scope3ExclusionReason->value ?? 'n/a');
    }
}
