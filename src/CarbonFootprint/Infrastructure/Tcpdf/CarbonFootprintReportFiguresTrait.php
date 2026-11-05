<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Tcpdf;

use App\CarbonFootprint\Application\Activity\ReportedEmissionsEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionDefinition;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CalculationMethod;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\DataQuality;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportCompensationLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportCompensationScope;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportBoundaryLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportExclusionLine;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationProjection;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationProjectionSource;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\Tcpdf\PdfKeyFigure;

trait CarbonFootprintReportFiguresTrait
{
    private const string INCLUDED_CALCULATED = 'Calculada';
    private const string INCLUDED_WITHOUT_ACTIVITY = 'Sin actividad declarada';
    private const string INCLUDED_EXCLUDED = 'Excluida';
    private const string INCLUDED_NOT_APPLICABLE = 'No aplicable';

    /** @param array<string, string> $parameters */
    private function trans(string $id, Locale $locale, array $parameters = []): string
    {
        return $this->translator->trans($id, $parameters, 'messages', $locale->value);
    }

    private function number(string|float $value, int $decimals = 2): string
    {
        return number_format((float) $value, $decimals, ',', '.');
    }

    private function tonnes(string $kilograms): string
    {
        return $this->number((float) $kilograms / 1000);
    }

    private function factorNumber(string $value): string
    {
        $decimals = str_contains($value, '.') ? strlen(rtrim(substr($value, (int) strpos($value, '.') + 1), '0')) : 0;

        return $this->number($value, min(6, max(2, $decimals)));
    }

    private function signedPercentage(float $value): string
    {
        return ($value < 0 ? '−' : '+') . $this->number(abs($value), 1) . ' %';
    }

    private function unitLabel(Unit $unit, Locale $locale): string
    {
        return $this->translator->trans($unit->value, [], 'unitType', $locale->value);
    }

    private function factorSourceLabel(?string $source, Locale $locale): string
    {
        return match ($source) {
            null => '-',
            ReportedEmissionsEmissionFactorSelector::SOURCE => $this->trans(CalculationMethod::ReportedByOrganization->label(), $locale),
            default => $source,
        };
    }

    /** @return list<PdfKeyFigure> */
    private function eventKeyFigures(EventCarbonFootprintReportData $data, Locale $locale): array
    {
        $figures = [
            new PdfKeyFigure($this->trans('Emisiones totales', $locale), $this->tonnes($data->totalEmissions->co2eq()), 't CO₂eq'),
            new PdfKeyFigure($this->trans('Emisiones por asistente', $locale), $this->number($data->emissionsPerAttendee->co2eq()), 'kg CO₂eq'),
            new PdfKeyFigure($this->trans('Fase con más emisiones', $locale), $this->translator->trans($this->maxKey($data->emissionsByPhase, Phase::Materials->value), [], 'phase', $locale->value)),
            new PdfKeyFigure($this->trans('Fuente con más emisiones', $locale), $this->translator->trans($this->maxKey($data->emissionsBySource, Source::Materials->value), [], 'source', $locale->value)),
        ];

        if ($data->electricityActivities > 0) {
            $figures[] = new PdfKeyFigure($this->trans('Electricidad · localización', $locale), $this->tonnes($data->locationBasedElectricityEmissions->co2eq()), 't CO₂eq');
        }

        $compensation = $data->compensation;

        if (null !== $compensation && $compensation->hasCompensations()) {
            $figures[] = new PdfKeyFigure($this->trans('Cobertura compensada', $locale), $this->number(min(100.0, $compensation->coveragePercentage)), '%');
        }

        return $figures;
    }

    /** @return list<PdfKeyFigure> */
    private function organizationalKeyFigures(OrganizationalCarbonFootprintReportData $data, Locale $locale): array
    {
        $figures = [
            new PdfKeyFigure($this->trans('Emisiones totales', $locale), $this->tonnes($data->totalEmissions->co2eq()), 't CO₂eq'),
            new PdfKeyFigure($this->translator->trans(Scope::Scope1->value, [], 'scope', $locale->value), $this->tonnes(($data->emissionsByScope[Scope::Scope1->value] ?? new Emissions())->co2eq()), 't CO₂eq'),
            new PdfKeyFigure($this->translator->trans(Scope::Scope2->value, [], 'scope', $locale->value), $this->tonnes(($data->emissionsByScope[Scope::Scope2->value] ?? new Emissions())->co2eq()), 't CO₂eq'),
            $data->scope3Excluded
                ? new PdfKeyFigure($this->translator->trans(Scope::Scope3->value, [], 'scope', $locale->value), $this->trans('Excluido', $locale))
                : new PdfKeyFigure($this->translator->trans(Scope::Scope3->value, [], 'scope', $locale->value), $this->tonnes(($data->emissionsByScope[Scope::Scope3->value] ?? new Emissions())->co2eq()), 't CO₂eq'),
            new PdfKeyFigure($this->trans('Fuente con más emisiones', $locale), $this->sourceLabel(Source::from($this->maxKey($data->emissionsBySource, Source::FixedFacilities->value)), $locale)),
            $data->precalculated
                ? new PdfKeyFigure($this->trans('Alcance 2 · localización', $locale), '---')
                : new PdfKeyFigure($this->trans('Alcance 2 · localización', $locale), $this->tonnes($data->locationBasedElectricityEmissions->co2eq()), 't CO₂eq'),
        ];

        if (CarbonFootprintReportSubjectType::Company === $data->subjectType) {
            $figures[] = new PdfKeyFigure($this->trans('Propiedades incluidas', $locale), (string) $this->includedProperties($data));
        }

        return $figures;
    }

    private function includedProperties(OrganizationalCarbonFootprintReportData $data): int
    {
        return count(array_filter($data->emissionsByProperty, static fn(array $row): bool => null !== $row['propertyId']));
    }

    /** @param array<string, Emissions> $emissions */
    private function maxKey(array $emissions, string $default): string
    {
        $maxKey = $default;
        $maxValue = -1.0;

        foreach ($emissions as $key => $value) {
            if ((float) $value->co2eq() > $maxValue) {
                $maxValue = (float) $value->co2eq();
                $maxKey = $key;
            }
        }

        return $maxKey;
    }

    /** @return list<list<string>> */
    private function eventDataRows(EventCarbonFootprintReportData $data, Locale $locale): array
    {
        return [
            [$this->trans('Nombre del evento', $locale), $data->eventName],
            [$this->trans('Sector', $locale), null !== $data->sector ? $this->translator->trans($data->sector->value, [], 'eventSectorType', $locale->value) : '-'],
            [$this->trans('Tipología', $locale), null !== $data->typology ? $this->translator->trans($data->typology->value, [], 'eventTypologyType', $locale->value) : '-'],
            [$this->trans('Tamaño', $locale), null !== $data->size ? $this->translator->trans($data->size->value, [], 'eventSizeType', $locale->value) : '-'],
            [$this->trans('Asistentes estimados', $locale), null !== $data->estimatedAttendees ? (string) $data->estimatedAttendees : '-'],
            [$this->trans('Montaje', $locale), (string) $data->assembly],
            [$this->trans('Celebración', $locale), (string) $data->celebration],
            [$this->trans('Desmontaje', $locale), (string) $data->breakdown],
            [$this->trans('Año de cálculo', $locale), (string) $data->calculationYear],
        ];
    }

    /** @return list<list<string>> */
    private function organizationDataRows(OrganizationalCarbonFootprintReportData $data, Locale $locale): array
    {
        $rows = [[CarbonFootprintReportSubjectType::Property === $data->subjectType ? $this->trans('Propiedad', $locale) : $this->trans('Empresa', $locale), $data->subjectName]];

        if (CarbonFootprintReportSubjectType::Property === $data->subjectType) {
            $rows[] = [$this->trans('Empresa', $locale), $data->companyName ?? '-'];
            $rows[] = [$this->trans('País', $locale), $data->countryName ?? '-'];
        }

        $rows[] = [$this->trans('Año de cálculo', $locale), (string) $data->calculationYear];
        $rows[] = [$this->trans('Grupo de coeficientes', $locale), $data->emissionFactorGroupName];

        return $rows;
    }

    /** @return list<list<string>> */
    private function emissionsRows(Emissions $emissions, Locale $locale): array
    {
        return [
            [$this->trans('CO₂eq (kg)', $locale), $this->number($emissions->co2eq())],
            [$this->trans('CO₂ (kg)', $locale), $this->number($emissions->co2())],
            [$this->trans('CH₄ (kg)', $locale), $this->number($emissions->ch4InKilograms())],
            [$this->trans('N₂O (kg)', $locale), $this->number($emissions->n2oInKilograms())],
        ];
    }

    private function co2eqOnlyNote(OrganizationalCarbonFootprintReportData $data, Locale $locale): ?string
    {
        if (!$data->hasCo2eqOnlyActivities()) {
            return null;
        }

        return $this->trans('Las fugas de gases refrigerantes y las emisiones comunicadas por la organización o sus proveedores se contabilizan solo en CO₂eq: no tienen desglose por gas y su valor se incluye en la fila de CO₂.', $locale);
    }

    /**
     * @param list<CalculationStage> $stages
     *
     * @return list<Source>
     */
    private function stageSources(array $stages, ?CarbonFootprintReportSubjectType $subjectType = null): array
    {
        $sources = [];

        foreach ($stages as $stage) {
            foreach ($this->sourcesOf($stage, $subjectType) as $source) {
                $sources[$source->value] = true;
            }
        }

        return array_values(array_filter(Source::cases(), static fn(Source $source): bool => isset($sources[$source->value])));
    }

    /** @return list<Source> */
    private function sourcesOf(CalculationStage $stage, ?CarbonFootprintReportSubjectType $subjectType = null): array
    {
        if ($stage instanceof Scope) {
            return null !== $subjectType ? $stage->sourcesFor($subjectType) : $stage->allSources();
        }

        return array_map(static fn(SectionDefinition $section): Source => $section->source, $stage->sections());
    }

    /**
     * @param array<string, Emissions> $emissionsBySource
     * @param list<CalculationStage>   $stages
     *
     * @return list<list<string>>
     */
    private function sourceRows(array $emissionsBySource, array $stages, Locale $locale, ?Emissions $totalEmissions = null): array
    {
        $rows = [];

        foreach ($this->stageSources($stages) as $source) {
            $emissions = ($emissionsBySource[$source->value] ?? new Emissions())->co2eq();

            if (null === $totalEmissions) {
                $rows[] = [$this->translator->trans($source->value, [], 'source', $locale->value), $this->number($emissions)];

                continue;
            }

            $rows[] = [$this->sourceLabel($source, $locale), $this->number($emissions), $this->percentage((float) $emissions, (float) $totalEmissions->co2eq())];
        }

        return $rows;
    }

    private function sourceLabel(Source $source, Locale $locale): string
    {
        $label = $this->translator->trans($source->value, [], 'source', $locale->value);
        $category = $source->ghgProtocolCategory();

        if (null === $category) {
            return $label;
        }

        return $this->trans('Cat. {number}', $locale, ['{number}' => (string) $category]) . ' · ' . $label;
    }

    private function percentage(float $part, float $total): string
    {
        return $this->number(0.0 === $total ? 0.0 : $part / $total * 100) . ' %';
    }

    /** @return list<list<string>> */
    private function phaseRows(EventCarbonFootprintReportData $data, Locale $locale): array
    {
        $rows = [];

        foreach (Phase::cases() as $phase) {
            $rows[] = [$this->translator->trans($phase->value, [], 'phase', $locale->value), $this->number($data->emissionsByPhase[$phase->value]->co2eq())];
        }

        return $rows;
    }

    /** @return list<list<string>> */
    private function phaseSourceRows(EventCarbonFootprintReportData $data, Locale $locale): array
    {
        $rows = [];

        foreach (Phase::cases() as $phase) {
            foreach ($this->stageSources([$phase]) as $source) {
                $rows[] = [
                    $this->translator->trans($phase->value, [], 'phase', $locale->value),
                    $this->translator->trans($source->value, [], 'source', $locale->value),
                    $this->number(($data->emissionsByPhaseAndSource[$phase->value][$source->value] ?? new Emissions())->co2eq()),
                ];
            }
        }

        return $rows;
    }

    /** @return list<list<string>> */
    private function scopeRows(OrganizationalCarbonFootprintReportData $data, Locale $locale): array
    {
        $rows = [];

        foreach (Scope::cases() as $scope) {
            $emissions = ($data->emissionsByScope[$scope->value] ?? new Emissions())->co2eq();
            $rows[] = [$this->translator->trans($scope->value, [], 'scope', $locale->value), $this->number($emissions), $this->percentage((float) $emissions, (float) $data->totalEmissions->co2eq())];
        }

        return $rows;
    }

    /** @return list<list<string>> */
    private function scopeSourceRows(OrganizationalCarbonFootprintReportData $data, Locale $locale): array
    {
        $rows = [];

        foreach (Scope::cases() as $scope) {
            foreach ($this->stageSources([$scope], $data->subjectType) as $source) {
                $emissions = ($data->emissionsByScopeAndSource[$scope->value][$source->value] ?? new Emissions())->co2eq();
                $rows[] = [
                    $this->translator->trans($scope->value, [], 'scope', $locale->value),
                    $this->sourceLabel($source, $locale),
                    $this->number($emissions),
                    $this->percentage((float) $emissions, (float) $data->totalEmissions->co2eq()),
                ];
            }
        }

        return $rows;
    }

    /** @return list<list<string>> */
    private function propertyRows(OrganizationalCarbonFootprintReportData $data, Locale $locale): array
    {
        $rows = [];

        foreach ($data->emissionsByProperty as $row) {
            $rows[] = [null === $row['propertyId'] ? $this->trans('Sin asignar a propiedad', $locale) : $row['name'], $this->number($row['emissions']->co2eq())];
        }

        return $rows;
    }

    /** @return list<list<string>> */
    private function includedEmissionsRows(OrganizationalCarbonFootprintReportData $data, Locale $locale): array
    {
        return array_map(fn(array $entry): array => [
            $this->translator->trans($entry['scope']->value, [], 'scope', $locale->value),
            $entry['label'],
            $this->trans($entry['status'], $locale),
            $entry['reason'],
            $entry['comment'],
        ], $this->includedEmissionsEntries($data, $locale));
    }

    /** @return list<array{scope: Scope, label: string, status: string, reason: string, comment: string}> */
    private function includedEmissionsEntries(OrganizationalCarbonFootprintReportData $data, Locale $locale): array
    {
        $entries = [];

        foreach (Scope::cases() as $scope) {
            if (Scope::Scope3 === $scope && $data->scope3Excluded) {
                $entries[] = [
                    'scope' => $scope,
                    'label' => $this->trans('Alcance completo', $locale),
                    'status' => self::INCLUDED_EXCLUDED,
                    'reason' => null === $data->scope3ExclusionReason ? '-' : $this->translator->trans($data->scope3ExclusionReason->value, [], 'exclusionReason', $locale->value),
                    'comment' => $data->scope3ExclusionComment ?? '-',
                ];

                continue;
            }

            if (Scope::Scope3 !== $scope) {
                foreach ($this->stageSources([$scope], $data->subjectType) as $source) {
                    $entries[] = $this->includedSourceEntry($data, $scope, $source, $locale);
                }

                continue;
            }

            foreach (range(1, 15) as $category) {
                $source = Source::ofGhgProtocolCategory($category);

                $entries[] = match (true) {
                    null === $source => [
                        'scope' => $scope,
                        'label' => $this->trans('Cat. {number}', $locale, ['{number}' => (string) $category]) . ' · ' . $this->trans('Activos arrendados aguas arriba', $locale),
                        'status' => self::INCLUDED_NOT_APPLICABLE,
                        'reason' => $this->trans('Control operacional', $locale),
                        'comment' => '-',
                    ],
                    !in_array($source, $scope->sourcesFor($data->subjectType), true) => [
                        'scope' => $scope,
                        'label' => $this->sourceLabel($source, $locale),
                        'status' => self::INCLUDED_NOT_APPLICABLE,
                        'reason' => $this->trans('Se informa en la huella de la empresa', $locale),
                        'comment' => '-',
                    ],
                    default => $this->includedSourceEntry($data, $scope, $source, $locale),
                };
            }
        }

        return $entries;
    }

    /** @return array{scope: Scope, label: string, status: string, reason: string, comment: string} */
    private function includedSourceEntry(OrganizationalCarbonFootprintReportData $data, Scope $scope, Source $source, Locale $locale): array
    {
        $exclusion = $this->exclusionFor($data, $scope, $source);

        return [
            'scope' => $scope,
            'label' => $this->sourceLabel($source, $locale),
            'status' => match (true) {
                null !== $exclusion => self::INCLUDED_EXCLUDED,
                $this->hasReportedSource($data, $scope, $source) => self::INCLUDED_CALCULATED,
                default => self::INCLUDED_WITHOUT_ACTIVITY,
            },
            'reason' => null === $exclusion ? '-' : $this->translator->trans($exclusion->reason->value, [], 'exclusionReason', $locale->value),
            'comment' => $exclusion->comment ?? '-',
        ];
    }

    /** @return list<array{0: Scope, 1: Source, 2: string}> */
    private function scopeSourceDefinitions(OrganizationalCarbonFootprintReportData $data, Locale $locale): array
    {
        $definitions = [];

        foreach (Scope::cases() as $scope) {
            if (Scope::Scope3 === $scope && $data->scope3Excluded) {
                continue;
            }

            foreach ($this->stageSources([$scope], $data->subjectType) as $source) {
                if (!$this->hasReportedSource($data, $scope, $source)) {
                    continue;
                }

                $definitions[] = [
                    $scope,
                    $source,
                    $this->translator->trans($scope->value, [], 'scope', $locale->value) . ' · ' . $this->sourceLabel($source, $locale),
                ];
            }
        }

        return $definitions;
    }

    private function hasReportedSource(OrganizationalCarbonFootprintReportData $data, Scope $scope, Source $source): bool
    {
        if (null !== $this->exclusionFor($data, $scope, $source)) {
            return true;
        }

        if ((float) ($data->emissionsByScopeAndSource[$scope->value][$source->value] ?? new Emissions())->co2eq() > 0.0) {
            return true;
        }

        return $data->hasSourceLines($scope, $source);
    }

    private function exclusionFor(OrganizationalCarbonFootprintReportData $data, Scope $scope, Source $source): ?OrganizationalCarbonFootprintReportExclusionLine
    {
        foreach ($data->exclusionLines as $line) {
            if ($line->scope === $scope && $line->source === $source) {
                return $line;
            }
        }

        return null;
    }

    /** @return array{0: list<string>, 1: list<list<string>>, 2: list<string>, 3: bool} */
    private function organizationalSourceTable(OrganizationalCarbonFootprintReportData $data, Scope $scope, Source $source, Locale $locale, bool $isCompany): array
    {
        return match (true) {
            Source::BuildingElectricity === $source && !$data->precalculated => $this->electricityTable($data, $locale, $isCompany),
            Source::FuelAndEnergyRelated === $source && !$data->precalculated => $this->derivedSourceTable($data, $locale),
            in_array($source, [Source::DownstreamLeasedAssets, Source::Franchises], true) => $this->nonConsolidatedPropertyTable($data, $source, $locale),
            default => $this->sourceTable($data, $scope, $source, $locale, $isCompany),
        };
    }

    /** @return array{0: list<string>, 1: list<list<string>>, 2: list<string>, 3: bool} */
    private function sourceTable(OrganizationalCarbonFootprintReportData $data, Scope $scope, Source $source, Locale $locale, bool $isCompany): array
    {
        $header = [$this->trans('Elemento', $locale)];
        $widths = [$isCompany ? '20%' : '30%'];

        if ($isCompany) {
            $header[] = $this->trans('Propiedad', $locale);
            $widths[] = '14%';
        }

        array_push($header, $this->trans('Cantidad', $locale), $this->trans('Factor', $locale), $this->trans('Año', $locale), $this->trans('Fuente del factor', $locale), $this->trans('Emisiones kgCO2eq', $locale));
        array_push($widths, '12%', '14%', '8%', $isCompany ? '18%' : '22%', '14%');

        $rows = [];
        $total = 0.0;

        foreach ($data->activityLines as $line) {
            if ($line->scope !== $scope || $line->source !== $source) {
                continue;
            }

            $row = [$line->itemName];

            if ($isCompany) {
                $row[] = $line->propertyName ?? $this->trans('Sin asignar a propiedad', $locale);
            }

            array_push($row, sprintf('%s %s', $this->number($line->quantity), $this->unitLabel($line->unit, $locale)), $this->factorCell($line->factorCo2eq, $line->factorUnit, $locale), (string) $line->factorYear, $this->factorSourceLabel($line->factorSource, $locale), $this->number($line->emissionsCo2eq));

            $rows[] = $row;
            $total += (float) $line->emissionsCo2eq;
        }

        $hasTotal = [] !== $rows;

        if ($hasTotal) {
            $totalRow = array_fill(0, count($header) - 1, '');
            $totalRow[0] = $this->trans('Total', $locale);
            $totalRow[] = $this->number($total);
            $rows[] = $totalRow;
        }

        return [$header, $rows, $widths, $hasTotal];
    }

    private function factorCell(string $factorCo2eq, Unit $factorUnit, Locale $locale): string
    {
        return sprintf('%s kg CO₂eq / %s', $this->factorNumber($factorCo2eq), $this->unitLabel($factorUnit, $locale));
    }

    /** @return array{0: list<string>, 1: list<list<string>>, 2: list<string>, 3: bool} */
    private function derivedSourceTable(OrganizationalCarbonFootprintReportData $data, Locale $locale): array
    {
        $header = [
            $this->trans('Alcance', $locale),
            $this->trans('Fuente', $locale),
            $this->trans('Tipo', $locale),
            $this->trans('Cantidad', $locale),
            $this->trans('Factor WTT', $locale),
            $this->trans('Año', $locale),
            $this->trans('Fuente del factor', $locale),
            $this->trans('Emisiones kgCO2eq', $locale),
        ];
        $widths = ['12%', '20%', '14%', '12%', '12%', '6%', '12%', '12%'];

        $rows = [];
        $total = 0.0;

        foreach ($data->derivedLines as $line) {
            if (Source::FuelAndEnergyRelated !== $line->source) {
                continue;
            }

            $rows[] = [
                $this->translator->trans($line->origin->stage->key(), [], 'scope', $locale->value),
                $this->sourceLabel($line->origin->source, $locale),
                $this->translator->trans($line->activityType->value, [], 'activityType', $locale->value),
                sprintf('%s %s', $this->number($line->quantity), $this->unitLabel($line->unit, $locale)),
                $this->factorCell($line->factorCo2eq, $line->factorUnit, $locale),
                (string) $line->factorYear,
                $this->factorSourceLabel($line->factorSource, $locale),
                $this->number($line->emissions->co2eq()),
            ];
            $total += (float) $line->emissions->co2eq();
        }

        $hasTotal = [] !== $rows;

        if ($hasTotal) {
            $rows[] = [$this->trans('Total', $locale), '', '', '', '', '', '', $this->number($total)];
        }

        return [$header, $rows, $widths, $hasTotal];
    }

    /** @return array{0: list<string>, 1: list<list<string>>, 2: list<string>, 3: bool} */
    private function nonConsolidatedPropertyTable(OrganizationalCarbonFootprintReportData $data, Source $source, Locale $locale): array
    {
        $header = [
            $this->trans('Propiedad', $locale),
            $this->trans('Origen', $locale),
            $this->trans('Emisiones calculadas kgCO2eq', $locale),
            $this->trans('Valor manual kgCO2eq', $locale),
            $this->trans('Emisiones kgCO2eq', $locale),
        ];
        $widths = ['30%', '22%', '16%', '16%', '16%'];

        $namesByProperty = [];
        foreach ($data->emissionsByProperty as $property) {
            if (null !== $property['propertyId']) {
                $namesByProperty[$property['propertyId']] = $property['name'];
            }
        }

        $rows = [];
        $total = 0.0;

        foreach ($data->nonConsolidatedPropertyLines as $line) {
            if ($line->source !== $source) {
                continue;
            }

            $origin = match (true) {
                null !== $line->manualCo2eq => $this->trans('Valor manual', $locale),
                null !== $line->operatorContributionId => $this->trans('Aportación del franquiciado o arrendatario', $locale),
                null !== $line->propertyCarbonFootprintId => $this->trans('Huella de la propiedad', $locale),
                default => $this->trans('Sin datos', $locale),
            };

            $rows[] = [
                $namesByProperty[(string) $line->propertyId] ?? (string) $line->propertyId,
                $origin,
                $this->number($line->calculatedEmissions->co2eq()),
                null === $line->manualCo2eq ? '-' : $this->number($line->manualCo2eq),
                $this->number($line->emissions->co2eq()),
            ];
            $total += (float) $line->emissions->co2eq();
        }

        $hasTotal = [] !== $rows;

        if ($hasTotal) {
            $rows[] = [$this->trans('Total', $locale), '', '', '', $this->number($total)];
        }

        return [$header, $rows, $widths, $hasTotal];
    }

    private function minimumBoundaryNote(Locale $locale): string
    {
        return $this->trans('Límite mínimo aplicado: emisiones de alcance 1 y 2 del operador de cada propiedad franquiciada o arrendada a terceros, por el método de mercado (Scope 3 Standard, categorías 13 y 14).', $locale);
    }

    /** @return array{0: list<string>, 1: list<list<string>>, 2: list<string>, 3: bool} */
    private function electricityTable(OrganizationalCarbonFootprintReportData $data, Locale $locale, bool $isCompany): array
    {
        $header = [$this->trans('Comercializadora', $locale)];
        $widths = [$isCompany ? '20%' : '30%'];

        if ($isCompany) {
            $header[] = $this->trans('Propiedad', $locale);
            $widths[] = '14%';
        }

        array_push($header, 'kWh', $this->trans('Factor', $locale), $this->trans('Emisiones mercado kgCO2eq', $locale), $this->trans('Emisiones localización kgCO2eq', $locale));
        array_push($widths, '14%', '14%', $isCompany ? '19%' : '21%', $isCompany ? '19%' : '21%');

        $rows = [];
        $marketTotal = 0.0;
        $locationTotal = 0.0;

        foreach ($data->activityLines as $line) {
            if (Scope::Scope2 !== $line->scope || Source::BuildingElectricity !== $line->source) {
                continue;
            }

            $row = [$line->itemName];

            if ($isCompany) {
                $row[] = $line->propertyName ?? $this->trans('Sin asignar a propiedad', $locale);
            }

            array_push($row, $this->number($line->quantity), $this->factorCell($line->factorCo2eq, $line->factorUnit, $locale), $this->number($line->emissionsCo2eq), null !== $line->locationBasedEmissionsCo2eq ? $this->number($line->locationBasedEmissionsCo2eq) : '---');

            $rows[] = $row;
            $marketTotal += (float) $line->emissionsCo2eq;
            $locationTotal += (float) ($line->locationBasedEmissionsCo2eq ?? '0');
        }

        $hasTotal = [] !== $rows;

        if ($hasTotal) {
            $totalRow = array_fill(0, count($header) - 2, '');
            $totalRow[0] = $this->trans('Total', $locale);
            $totalRow[] = $this->number($marketTotal);
            $totalRow[] = $this->number($locationTotal);
            $rows[] = $totalRow;
        }

        return [$header, $rows, $widths, $hasTotal];
    }

    /** @return list<list<string>> */
    private function organizationalDualReportingRows(OrganizationalCarbonFootprintReportData $data, Locale $locale): array
    {
        $rows = [
            [$this->trans('Alcance 2 · método de mercado (kg CO₂eq)', $locale), $this->number(($data->emissionsByScope[Scope::Scope2->value] ?? new Emissions())->co2eq())],
            [$this->trans('Alcance 2 · método de localización (kg CO₂eq)', $locale), $data->precalculated ? '---' : $this->number($data->locationBasedElectricityEmissions->co2eq())],
            [$this->trans('Total · método de localización (kg CO₂eq)', $locale), $data->precalculated ? '---' : $this->number($data->locationBasedTotalEmissions->co2eq())],
        ];

        if ($data->electricityActivitiesWithoutLocationBased > 0) {
            $rows[] = [$this->trans('Actividades de electricidad sin factor de mix eléctrico', $locale), (string) $data->electricityActivitiesWithoutLocationBased];
        }

        return $rows;
    }

    /** @return list<list<string>> */
    private function eventDualReportingRows(EventCarbonFootprintReportData $data, Locale $locale): array
    {
        if (0 === $data->electricityActivities) {
            return [];
        }

        $rows = [
            [$this->trans('Electricidad · método de mercado (kg CO₂eq)', $locale), $this->number($data->marketBasedElectricityEmissions->co2eq())],
            [$this->trans('Electricidad · método de localización (kg CO₂eq)', $locale), $this->number($data->locationBasedElectricityEmissions->co2eq())],
            [$this->trans('Total · método de localización (kg CO₂eq)', $locale), $this->number($data->locationBasedTotalEmissions->co2eq())],
        ];

        if ($data->electricityActivitiesWithoutLocationBased > 0) {
            $rows[] = [$this->trans('Actividades de electricidad sin factor de mix eléctrico', $locale), (string) $data->electricityActivitiesWithoutLocationBased];
        }

        return $rows;
    }

    /** @return array{0: list<string>, 1: list<list<string>>, 2: list<string>, 3: bool} */
    private function eventElectricityTable(EventCarbonFootprintReportData $data, Locale $locale): array
    {
        $header = [$this->trans('Actividad', $locale), $this->trans('Fase', $locale), 'kWh', $this->trans('Factor', $locale), $this->trans('Emisiones mercado kgCO2eq', $locale), $this->trans('Emisiones localización kgCO2eq', $locale)];
        $widths = ['26%', '14%', '12%', '12%', '18%', '18%'];

        $rows = [];
        $marketTotal = 0.0;
        $locationTotal = 0.0;

        foreach ($data->activityLines as $line) {
            if (ActivityType::Electricity !== $line->activityType) {
                continue;
            }

            $rows[] = [
                $line->itemName,
                $this->translator->trans($line->phase->value, [], 'phase', $locale->value),
                $this->number($line->quantity),
                $this->factorCell($line->factorCo2eq, $line->factorUnit, $locale),
                $this->number($line->emissionsCo2eq),
                null !== $line->locationBasedEmissionsCo2eq ? $this->number($line->locationBasedEmissionsCo2eq) : '---',
            ];
            $marketTotal += (float) $line->emissionsCo2eq;
            $locationTotal += (float) ($line->locationBasedEmissionsCo2eq ?? '0');
        }

        $hasTotal = [] !== $rows;

        if ($hasTotal) {
            $totalRow = array_fill(0, count($header) - 2, '');
            $totalRow[0] = $this->trans('Total', $locale);
            $totalRow[] = $this->number($marketTotal);
            $totalRow[] = $this->number($locationTotal);
            $rows[] = $totalRow;
        }

        return [$header, $rows, $widths, $hasTotal];
    }

    /** @return list<list<string>> */
    private function compensationSummaryRows(EventCarbonFootprintReportData $data, Locale $locale): array
    {
        $compensation = $data->compensation;
        assert(null !== $compensation);

        return [
            [$this->trans('Emisiones totales del evento (kg CO₂eq)', $locale), $this->number($data->totalEmissions->co2eq())],
            [$this->trans('Compensado por la empresa organizadora (t CO₂eq)', $locale), $this->number($compensation->ownTonnes, 3)],
            [$this->trans('Compensado por los asistentes (t CO₂eq)', $locale), $this->number($compensation->attendeesTonnes, 3)],
            [$this->trans('Total compensado (t CO₂eq)', $locale), $this->number($compensation->totalTonnes, 3)],
            [$this->trans('Cobertura sobre las emisiones totales', $locale), $this->number(min(100.0, $compensation->coveragePercentage)) . ' %'],
            [$this->trans('Fecha de corte de los datos de compensación', $locale), $compensation->cutOffAt->format('d/m/Y')],
        ];
    }

    /** @return list<string> */
    private function compensationProjectsHeader(Locale $locale): array
    {
        return [
            $this->trans('Ámbito', $locale),
            $this->trans('Proyecto', $locale),
            $this->trans('Ubicación', $locale),
            $this->trans('Registro', $locale),
            $this->trans('Compensaciones', $locale),
            $this->trans('Toneladas', $locale),
        ];
    }

    /** @return list<list<string>> */
    private function compensationProjectRows(EventCarbonFootprintReportData $data, Locale $locale): array
    {
        $rows = [];

        foreach ($data->compensation->projectLines ?? [] as $projectLine) {
            $rows[] = [
                $this->transScope($projectLine->scope, $locale),
                $projectLine->projectName,
                $projectLine->projectLocation ?? '-',
                $projectLine->projectRegistryId ?? '-',
                (string) $projectLine->contributions,
                $this->number($projectLine->tonnes, 3),
            ];
        }

        return $rows;
    }

    /** @return list<string> */
    private function traceabilityHeader(Locale $locale): array
    {
        return [
            $this->trans('Certificado', $locale),
            $this->trans('Fecha', $locale),
            $this->trans('Ámbito', $locale),
            $this->trans('Beneficiario', $locale),
            $this->trans('Proyecto', $locale),
            $this->trans('Registro', $locale),
            $this->trans('Toneladas', $locale),
            $this->trans('Serials y lotes', $locale),
            $this->trans('Verificación', $locale),
        ];
    }

    /** @return list<list<string>> */
    private function traceabilityRows(EventCarbonFootprintReportData $data, Locale $locale): array
    {
        $rows = [];

        foreach ($data->compensation->traceabilityLines ?? [] as $line) {
            $rows[] = [
                $line->certificateCode ?? '-',
                $line->issuedAt->format('d/m/Y'),
                $this->transScope($line->scope, $locale),
                $line->beneficiaryName ?? '-',
                $line->projectName,
                $line->projectRegistryId ?? '-',
                $this->number($line->tonnes, 3),
                $this->serialsCell($line),
                $line->verificationUrl ?? '-',
            ];
        }

        return $rows;
    }

    private function serialsCell(EventCarbonFootprintReportCompensationLine $line): string
    {
        $parts = [];

        foreach ($line->creditLots as $creditLot) {
            $parts[] = sprintf('%s (%s–%s)', $creditLot['serial'], $this->number($creditLot['startTonnes'], 3), $this->number($creditLot['endTonnes'], 3));
        }

        if ([] === $parts) {
            $parts = $line->masterSerials;
        }

        return [] === $parts ? '-' : implode(' / ', $parts);
    }

    private function transScope(EventCarbonFootprintReportCompensationScope $scope, Locale $locale): string
    {
        return match ($scope) {
            EventCarbonFootprintReportCompensationScope::OwnCompany => $this->trans('Empresa organizadora', $locale),
            EventCarbonFootprintReportCompensationScope::Attendees => $this->trans('Asistentes', $locale),
        };
    }

    /** @return list<list<string>> */
    private function eventExclusionRows(EventCarbonFootprintReportData $data, Locale $locale): array
    {
        $rows = [];

        foreach ($data->exclusionLines as $exclusionLine) {
            $rows[] = [
                $this->translator->trans($exclusionLine->phase->value, [], 'phase', $locale->value),
                $this->translator->trans($exclusionLine->source->value, [], 'source', $locale->value),
                $this->translator->trans($exclusionLine->reason->value, [], 'exclusionReason', $locale->value),
                $exclusionLine->comment ?? '-',
            ];
        }

        return $rows;
    }

    /** @return list<list<string>> */
    private function organizationalExclusionRows(OrganizationalCarbonFootprintReportData $data, Locale $locale): array
    {
        $rows = [];

        foreach ($data->exclusionLines as $exclusionLine) {
            $rows[] = [
                $this->translator->trans($exclusionLine->scope->value, [], 'scope', $locale->value),
                null === $exclusionLine->source ? $this->trans('Alcance completo', $locale) : $this->sourceLabel($exclusionLine->source, $locale),
                $this->translator->trans($exclusionLine->reason->value, [], 'exclusionReason', $locale->value),
                $exclusionLine->comment ?? '-',
            ];
        }

        return $rows;
    }

    /** @return list<string> */
    private function eventAnnexHeader(Locale $locale): array
    {
        return [
            $this->trans('Actividad', $locale),
            $this->trans('Fase', $locale),
            $this->trans('Fuente', $locale),
            $this->trans('Cantidad', $locale),
            $this->trans('Factor kgCO2eq/unidad', $locale),
            $this->trans('Año', $locale),
            $this->trans('Fuente del factor', $locale),
            $this->trans('Emisiones kgCO2eq', $locale),
        ];
    }

    /** @return list<list<string>> */
    private function eventAnnexRows(EventCarbonFootprintReportData $data, Locale $locale): array
    {
        $rows = [];

        foreach ($data->activityLines as $activityLine) {
            $rows[] = [
                $activityLine->itemName,
                $this->translator->trans($activityLine->phase->value, [], 'phase', $locale->value),
                $this->translator->trans($activityLine->source->value, [], 'source', $locale->value),
                sprintf('%s %s', $this->number($activityLine->quantity), $this->unitLabel($activityLine->unit, $locale)),
                $this->factorCell($activityLine->factorCo2eq, $activityLine->factorUnit, $locale),
                (string) $activityLine->factorYear,
                $activityLine->factorSource ?? '-',
                $this->number($activityLine->emissionsCo2eq),
            ];
        }

        return $rows;
    }

    /** @return list<string> */
    private function organizationalAnnexHeader(Locale $locale, bool $isCompany): array
    {
        $header = [$this->trans('Actividad', $locale), $this->trans('Alcance', $locale)];

        if ($isCompany) {
            $header[] = $this->trans('Propiedad', $locale);
        }

        array_push($header, $this->trans('Fuente', $locale), $this->trans('Cantidad', $locale), $this->trans('Factor kgCO2eq/unidad', $locale), $this->trans('Año', $locale), $this->trans('Fuente del factor', $locale), $this->trans('Emisiones kgCO2eq', $locale));

        return $header;
    }

    /** @return list<list<string>> */
    private function organizationalAnnexRows(OrganizationalCarbonFootprintReportData $data, Locale $locale, bool $isCompany): array
    {
        $rows = [];

        foreach ($data->activityLines as $line) {
            $row = [$line->itemName, $this->translator->trans($line->scope->value, [], 'scope', $locale->value)];

            if ($isCompany) {
                $row[] = $line->propertyName ?? $this->trans('Sin asignar a propiedad', $locale);
            }

            array_push($row, $this->sourceLabel($line->source, $locale), sprintf('%s %s', $this->number($line->quantity), $this->unitLabel($line->unit, $locale)), $this->factorCell($line->factorCo2eq, $line->factorUnit, $locale), (string) $line->factorYear, $this->factorSourceLabel($line->factorSource, $locale), $this->number($line->emissionsCo2eq));

            $rows[] = $row;
        }

        return $rows;
    }

    /** @return list<list<string>> */
    private function boundaryRows(OrganizationalCarbonFootprintReportData $data, Locale $locale): array
    {
        $isProperty = CarbonFootprintReportSubjectType::Property === $data->subjectType;
        $rows = [
            [$isProperty ? $this->trans('Propiedad', $locale) : $this->trans('Empresa', $locale), $data->subjectName],
            [$this->trans('Enfoque de consolidación', $locale), $this->trans('Control operacional', $locale)],
            ...$this->baseYearRows($data, $locale),
        ];

        if ($isProperty && null !== $data->operatingRegime) {
            $rows[] = [$this->trans('Régimen de explotación', $locale), $this->translator->trans($data->operatingRegime->value, [], 'operatingRegime', $locale->value)];
        }

        $rows[] = [$this->trans('Año de cálculo', $locale), (string) $data->calculationYear];

        $scopes = $data->scope3Excluded ? [Scope::Scope1, Scope::Scope2] : Scope::cases();
        $rows[] = [$this->trans('Alcances incluidos', $locale), implode(', ', array_map(fn(Scope $scope): string => $this->translator->trans($scope->value, [], 'scope', $locale->value), $scopes))];

        if ($data->scope3Excluded) {
            $reason = null === $data->scope3ExclusionReason ? '-' : $this->translator->trans($data->scope3ExclusionReason->value, [], 'exclusionReason', $locale->value);
            $rows[] = [$this->translator->trans(Scope::Scope3->value, [], 'scope', $locale->value), $this->trans('Excluido', $locale) . ' · ' . $reason];

            return $rows;
        }

        $categories = array_filter(array_map(static fn(Source $source): ?int => $source->ghgProtocolCategory(), Scope::Scope3->sourcesFor($data->subjectType)));
        sort($categories);

        $rows[] = [$this->trans('Categorías del alcance 3 contempladas', $locale), implode(', ', array_map(fn(int $category): string => $this->trans('Cat. {number}', $locale, ['{number}' => (string) $category]), $categories))];

        return $rows;
    }

    /** @return list<list<string>> */
    private function baseYearRows(OrganizationalCarbonFootprintReportData $data, Locale $locale): array
    {
        $baseYear = $data->baseYear;

        if (null === $baseYear) {
            return [[$this->trans('Año base', $locale), $this->trans('No declarado', $locale)]];
        }

        $variation = $data->baseYearVariationPercent();

        return [
            [$this->trans('Año base', $locale), (string) $baseYear->year],
            [$this->trans('Emisiones del año base (kg CO₂eq)', $locale), $this->number($baseYear->baseYearTotalKgCo2eq)],
            [$this->trans('Umbral de recálculo', $locale), $this->number($baseYear->recalculationThresholdPercent, 1) . ' %'],
            [$this->trans('Variación respecto al año base', $locale), match (true) {
                $data->isBaseYear() => $this->trans('Esta huella es el año base', $locale),
                null === $variation && $data->baseYearExceedsThreshold() => '— · ' . $this->trans('supera el umbral de recálculo', $locale),
                null === $variation => '—',
                $data->baseYearExceedsThreshold() => $this->signedPercentage($variation) . ' · ' . $this->trans('supera el umbral de recálculo', $locale),
                default => $this->signedPercentage($variation),
            }],
            [$this->trans('Justificación del año base', $locale), $baseYear->justification],
        ];
    }

    /** @return array{0: list<string>, 1: list<list<string>>, 2: list<string>} */
    private function providerContributionRows(OrganizationalCarbonFootprintReportData $data, Locale $locale): array
    {
        $isCompany = CarbonFootprintReportSubjectType::Company === $data->subjectType;
        $header = [$this->trans('Proveedor', $locale)];

        if ($isCompany) {
            $header[] = $this->trans('Propiedad', $locale);
        }

        array_push($header, $this->trans('Categorías', $locale), $this->trans('Modo', $locale), $this->trans('Estado', $locale), $this->trans('Fecha de respuesta', $locale), $this->trans('Emisiones kgCO2eq', $locale));
        $widths = $isCompany ? ['18%', '14%', '26%', '10%', '10%', '10%', '12%'] : ['22%', '32%', '12%', '10%', '10%', '14%'];

        $rows = [];
        $total = 0.0;

        foreach ($data->providerLines as $line) {
            $row = [$line->providerName];

            if ($isCompany) {
                $row[] = $line->propertyName ?? $this->trans('Sin asignar a propiedad', $locale);
            }

            array_push(
                $row,
                implode(' · ', array_map(fn(Source $source): string => $this->sourceLabel($source, $locale), $line->sources)),
                $this->translator->trans($line->mode->value, [], 'providerContributionMode', $locale->value),
                $this->translator->trans($line->status->value, [], 'providerContributionStatus', $locale->value),
                $line->answeredAt?->format('d/m/Y') ?? '-',
                $this->number($line->emissionsCo2eq),
            );

            $rows[] = $row;
            $total += (float) $line->emissionsCo2eq;
        }

        if ([] !== $rows) {
            $totalRow = array_fill(0, count($header) - 1, '');
            $totalRow[0] = $this->trans('Total', $locale);
            $totalRow[] = $this->number($total);
            $rows[] = $totalRow;
        }

        return [$header, $rows, $widths];
    }

    /** @return list<string> */
    private function supplierDataShareRow(OrganizationalCarbonFootprintReportData $data, Locale $locale): array
    {
        $share = $data->supplierDataSharePercent();

        return [$this->trans('% de las emisiones del alcance 3 con datos aportados por proveedores', $locale), null === $share ? '—' : $this->number($share, 1) . ' %'];
    }

    /** @return list<list<string>> */
    private function employeeSurveyRows(OrganizationalCarbonFootprintReportData $data, Locale $locale): array
    {
        $survey = $data->employeeSurvey;

        if (null === $survey || !$survey->hasSurvey()) {
            return [[$this->trans('No se ha realizado encuesta de empleados', $locale), '-']];
        }

        $participation = $survey->participationPercent();

        return [
            [$this->trans('Plantilla total', $locale), null === $survey->totalEmployees ? '-' : (string) $survey->totalEmployees],
            [$this->trans('Respuestas recibidas', $locale), (string) $survey->answeredCount],
            [$this->trans('Tasa de participación', $locale), null === $participation ? '-' : $this->number($participation, 1) . ' %'],
            [$this->trans('Nivel de confianza', $locale), null === $survey->confidenceLevel ? '-' : $survey->confidenceLevel->value . ' %'],
            [$this->trans('Margen de error', $locale), null === $survey->marginOfError ? '-' : '±' . $this->number($survey->marginOfError * 100) . ' %'],
            [$this->trans('Método de extrapolación', $locale), $this->trans('Los kilómetros y las noches declarados por los empleados encuestados se extrapolan al total de la plantilla por proporción (plantilla total / respuestas)', $locale)],
            [$this->trans('Categorías alimentadas', $locale), $this->sourceLabel(Source::BusinessTravel, $locale) . ', ' . $this->sourceLabel(Source::EmployeeCommuting, $locale)],
        ];
    }

    /** @return list<list<string>> */
    private function boundaryPropertyRows(OrganizationalCarbonFootprintReportData $data, Locale $locale): array
    {
        $rows = [];

        foreach ($data->boundaryLines as $line) {
            $rows[] = [$line->propertyName, $this->translator->trans($line->regime->value, [], 'operatingRegime', $locale->value), $this->boundaryTreatment($data, $line, $locale)];
        }

        return $rows;
    }

    private function boundaryTreatment(OrganizationalCarbonFootprintReportData $data, OrganizationalCarbonFootprintReportBoundaryLine $line, Locale $locale): string
    {
        if ($line->consolidated) {
            return $this->trans('Consolidada: alcances 1 y 2', $locale);
        }

        $source = $data->boundaryTreatment($line);

        if (null === $source) {
            return $this->trans('Fuera del cálculo', $locale);
        }

        return $this->translator->trans(Scope::Scope3->value, [], 'scope', $locale->value) . ' · ' . $this->sourceLabel($source, $locale);
    }

    /** @return list<list<string>> */
    private function categoryMethodRows(OrganizationalCarbonFootprintReportData $data, Locale $locale): array
    {
        $rows = [];

        foreach ($data->categoryMethodEmissions() as [$scope, $source, $methods]) {
            $categoryTotal = array_sum($methods);

            foreach ($methods as $method => $emissions) {
                $rows[] = [
                    $this->translator->trans($scope->value, [], 'scope', $locale->value),
                    $this->sourceLabel($source, $locale),
                    $this->trans(CalculationMethod::from($method)->label(), $locale),
                    $this->number($emissions),
                    $this->percentage($emissions, $categoryTotal),
                ];
            }
        }

        return $rows;
    }

    /** @return list<list<string>> */
    private function emissionFactorSourceRows(OrganizationalCarbonFootprintReportData $data, Locale $locale): array
    {
        $rows = [];

        foreach ($data->emissionFactorSourceSummary() as $row) {
            $label = match (true) {
                null !== $row['method'] && null !== $row['source'] => $this->trans($row['method']->label(), $locale) . ' · ' . $this->factorSourceLabel($row['source'], $locale),
                null !== $row['method'] => $this->trans($row['method']->label(), $locale),
                null !== $row['source'] => $this->factorSourceLabel($row['source'], $locale),
                default => $this->trans('Sin fuente declarada', $locale),
            };

            $rows[] = [$label, [] === $row['years'] ? '-' : implode(', ', $row['years']), (string) $row['lines'], $this->number($row['emissions'])];
        }

        return $rows;
    }

    /** @return list<list<string>> */
    private function emissionFactorGroupRows(OrganizationalCarbonFootprintReportData $data, Locale $locale): array
    {
        return [
            [$this->trans('Grupo de coeficientes', $locale), $data->emissionFactorGroupName],
            [$this->trans('Potencial de calentamiento global CH₄', $locale), $this->number($data->gwpCh4, 1)],
            [$this->trans('Potencial de calentamiento global N₂O', $locale), $this->number($data->gwpN2O, 1)],
        ];
    }

    /** @return list<list<string>> */
    private function dataQualityRows(OrganizationalCarbonFootprintReportData $data, Locale $locale): array
    {
        $rows = [];

        foreach ($data->categoryMethodEmissions() as [$scope, $source, $methods]) {
            $calculationMethods = array_map(static fn(int|string $method): CalculationMethod => CalculationMethod::from((string) $method), array_keys($methods));
            $quality = DataQuality::worstOf(...array_map(static fn(CalculationMethod $method): DataQuality => $method->dataQuality(), $calculationMethods));

            $observations = [];

            if (in_array(CalculationMethod::EmployeeSurveyExtrapolation, $calculationMethods, true)) {
                $survey = $data->employeeSurvey;
                $observations[] = null === $survey || null === $survey->marginOfError || null === $survey->confidenceLevel
                    ? $this->trans('Sin datos de encuesta', $locale)
                    : sprintf('±%s %% (%d %%)', $this->number($survey->marginOfError * 100), $survey->confidenceLevel->value);
            }

            if (in_array(CalculationMethod::ManualValue, $calculationMethods, true)) {
                $observations[] = $this->trans('Valor manual', $locale);
            }

            if (Source::BuildingElectricity === $source && $data->electricityActivitiesWithoutLocationBased > 0) {
                $observations[] = $this->trans('Sin factor de mix eléctrico', $locale);
            }

            $rows[] = [
                $this->translator->trans($scope->value, [], 'scope', $locale->value),
                $this->sourceLabel($source, $locale),
                $this->trans($quality->label(), $locale),
                [] === $observations ? '-' : implode(' · ', $observations),
            ];
        }

        return $rows;
    }

    /** @return list<int|null> */
    private function mitigationYearColumns(MitigationProjection $projection): array
    {
        $years = $projection->years;

        if (count($years) <= 6) {
            return $years;
        }

        return [...array_slice($years, 0, 4), null, $years[count($years) - 1]];
    }

    /** @return array{0: list<string>, 1: list<list<string>>, 2: list<string>} */
    private function mitigationRateRows(MitigationProjection $projection, Locale $locale): array
    {
        $columns = $this->mitigationYearColumns($projection);
        $rows = [];

        foreach ($projection->sources as $projectionSource) {
            $row = $this->mitigationSourceCells($projectionSource, $locale);

            foreach ($columns as $year) {
                $row[] = null === $year ? '…' : $this->number(($projectionSource->rateByYear[$year] ?? 0.0) * 100) . ' %';
            }

            $rows[] = $row;
        }

        return [$this->mitigationHeader($columns, $locale), $rows, $this->mitigationWidths(count($columns))];
    }

    /** @return array{0: list<string>, 1: list<list<string>>, 2: list<string>} */
    private function mitigationReductionRows(MitigationProjection $projection, Locale $locale): array
    {
        $columns = $this->mitigationYearColumns($projection);
        $rows = [];

        foreach ($projection->sources as $projectionSource) {
            $row = $this->mitigationSourceCells($projectionSource, $locale);

            foreach ($columns as $year) {
                $row[] = null === $year ? '…' : $this->number($projectionSource->reductionByYear[$year] ?? 0.0);
            }

            $rows[] = $row;
        }

        return [$this->mitigationHeader($columns, $locale), $rows, $this->mitigationWidths(count($columns))];
    }

    /** @return array{0: list<string>, 1: list<list<string>>, 2: list<string>} */
    private function mitigationExpectedRows(MitigationProjection $projection, Locale $locale): array
    {
        $columns = [$projection->baseYear, ...$this->mitigationYearColumns($projection)];
        $rows = [];

        foreach ($projection->sources as $projectionSource) {
            $row = $this->mitigationSourceCells($projectionSource, $locale);

            foreach ($columns as $year) {
                $row[] = null === $year ? '…' : $this->number($projectionSource->expectedByYear[$year] ?? 0.0);
            }

            $rows[] = $row;
        }

        $totalRow = [$this->trans('Total', $locale), '', ''];
        $interannualRow = [$this->trans('Reducción interanual %', $locale), '', ''];

        foreach ($columns as $index => $year) {
            $totalRow[] = null === $year ? '…' : $this->number($projection->totalByYear[$year] ?? 0.0);
            $interannualRow[] = match (true) {
                null === $year => '…',
                0 === $index => '-',
                default => $this->number(($projection->interannualReductionByYear[$year] ?? 0.0) * 100) . ' %',
            };
        }

        $rows[] = $totalRow;
        $rows[] = $interannualRow;

        $header = $this->mitigationHeader($this->mitigationYearColumns($projection), $locale);
        array_splice($header, 3, 0, [sprintf('%d %s', $projection->baseYear, $this->trans('Año base', $locale))]);

        return [$header, $rows, $this->mitigationWidths(count($columns))];
    }

    /** @return list<string> */
    private function mitigationSourceCells(MitigationProjectionSource $projectionSource, Locale $locale): array
    {
        return [
            $this->translator->trans($projectionSource->emissionSource->scope->value, [], 'scope', $locale->value),
            $this->sourceLabel($projectionSource->emissionSource->source, $locale),
            $projectionSource->emissionSource->itemName,
        ];
    }

    /**
     * @param list<int|null> $columns
     *
     * @return list<string>
     */
    private function mitigationHeader(array $columns, Locale $locale): array
    {
        $header = [$this->trans('Alcance', $locale), $this->trans('Fuente', $locale), $this->trans('Elemento', $locale)];

        foreach ($columns as $year) {
            $header[] = null === $year ? '…' : (string) $year;
        }

        return $header;
    }

    /** @return list<string> */
    private function mitigationWidths(int $yearColumns): array
    {
        $yearWidth = sprintf('%s%%', number_format(60 / max(1, $yearColumns), 2, '.', ''));

        return ['10%', '14%', '16%', ...array_fill(0, $yearColumns, $yearWidth)];
    }
}
