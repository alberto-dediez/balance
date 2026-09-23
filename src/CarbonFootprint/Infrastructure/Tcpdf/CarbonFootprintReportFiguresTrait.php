<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Tcpdf;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportCompensationLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportCompensationScope;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportExclusionLine;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\Tcpdf\PdfKeyFigure;

trait CarbonFootprintReportFiguresTrait
{
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

    /** @return list<PdfKeyFigure> */
    private function eventKeyFigures(EventCarbonFootprintReportData $data, Locale $locale): array
    {
        $figures = [
            new PdfKeyFigure($this->trans('Emisiones totales', $locale), $this->tonnes($data->totalEmissions->co2eq()), 't CO₂eq'),
            new PdfKeyFigure($this->trans('Emisiones por asistente', $locale), $this->number($data->emissionsPerAttendee->co2eq()), 'kg CO₂eq'),
            new PdfKeyFigure($this->trans('Fase con más emisiones', $locale), $this->translator->trans($this->maxKey($data->emissionsByPhase, Phase::Materials->value), [], 'phase', $locale->value)),
            new PdfKeyFigure($this->trans('Fuente con más emisiones', $locale), $this->translator->trans($this->maxKey($data->emissionsBySource, Source::Materials->value), [], 'source', $locale->value)),
        ];

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
            new PdfKeyFigure($this->trans('Fuente con más emisiones', $locale), $this->translator->trans($this->maxKey($data->emissionsBySource, Source::FixedFacilities->value), [], 'source', $locale->value)),
            new PdfKeyFigure($this->trans('Electricidad por localización', $locale), $this->tonnes($data->locationBasedElectricityEmissions->co2eq()), 't CO₂eq'),
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
    private function emissionsRows(Emissions $emissions): array
    {
        return [
            ['CO₂eq (kg)', $this->number($emissions->co2eq())],
            ['CO₂ (kg)', $this->number($emissions->co2())],
            ['CH₄ (kg)', $this->number($emissions->ch4())],
            ['N₂O (kg)', $this->number($emissions->n2o())],
        ];
    }

    /**
     * @param list<CalculationStage> $stages
     *
     * @return list<Source>
     */
    private function stageSources(array $stages): array
    {
        $sources = [];

        foreach ($stages as $stage) {
            foreach ($stage->sections() as $section) {
                $sources[$section->source->value] = true;
            }
        }

        return array_values(array_filter(Source::cases(), static fn(Source $source): bool => isset($sources[$source->value])));
    }

    /**
     * @param array<string, Emissions> $emissionsBySource
     * @param list<CalculationStage>   $stages
     *
     * @return list<list<string>>
     */
    private function sourceRows(array $emissionsBySource, array $stages, Locale $locale): array
    {
        $rows = [];

        foreach ($this->stageSources($stages) as $source) {
            $rows[] = [$this->translator->trans($source->value, [], 'source', $locale->value), $this->number(($emissionsBySource[$source->value] ?? new Emissions())->co2eq())];
        }

        return $rows;
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
            $rows[] = [$this->translator->trans($scope->value, [], 'scope', $locale->value), $this->number(($data->emissionsByScope[$scope->value] ?? new Emissions())->co2eq())];
        }

        return $rows;
    }

    /** @return list<list<string>> */
    private function scopeSourceRows(OrganizationalCarbonFootprintReportData $data, Locale $locale): array
    {
        $rows = [];

        foreach (Scope::cases() as $scope) {
            foreach ($scope->sections() as $section) {
                $rows[] = [
                    $this->translator->trans($scope->value, [], 'scope', $locale->value),
                    $this->translator->trans($section->source->value, [], 'source', $locale->value),
                    $this->number(($data->emissionsByScopeAndSource[$scope->value][$section->source->value] ?? new Emissions())->co2eq()),
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
        $rows = [];

        foreach (Scope::cases() as $scope) {
            foreach ($scope->sections() as $section) {
                $exclusion = $this->exclusionFor($data, $scope, $section->source);

                $rows[] = [
                    $this->translator->trans($scope->value, [], 'scope', $locale->value),
                    $this->translator->trans($section->source->value, [], 'source', $locale->value),
                    null === $exclusion ? $this->trans('Sí', $locale) : $this->trans('No', $locale),
                    null === $exclusion ? '-' : $this->translator->trans($exclusion->reason->value, [], 'exclusionReason', $locale->value),
                    $exclusion->comment ?? '-',
                ];
            }
        }

        return $rows;
    }

    /** @return list<array{0: Scope, 1: Source, 2: string}> */
    private function scopeSourceDefinitions(Locale $locale): array
    {
        $definitions = [];

        foreach (Scope::cases() as $scope) {
            foreach ($scope->sections() as $section) {
                $definitions[] = [
                    $scope,
                    $section->source,
                    $this->translator->trans($scope->value, [], 'scope', $locale->value) . ' · ' . $this->translator->trans($section->source->value, [], 'source', $locale->value),
                ];
            }
        }

        return $definitions;
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
    private function sourceTable(OrganizationalCarbonFootprintReportData $data, Scope $scope, Source $source, Locale $locale, bool $isCompany): array
    {
        $header = [$this->trans('Elemento', $locale)];
        $widths = [$isCompany ? '20%' : '30%'];

        if ($isCompany) {
            $header[] = $this->trans('Propiedad', $locale);
            $widths[] = '14%';
        }

        array_push($header, $this->trans('Cantidad', $locale), $this->trans('Factor kgCO2eq/unidad', $locale), $this->trans('Año', $locale), $this->trans('Fuente del factor', $locale), $this->trans('Emisiones kgCO2eq', $locale));
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

            array_push($row, sprintf('%s %s', $this->number($line->quantity), $line->unit->value), $line->factorCo2eq, (string) $line->factorYear, $line->factorSource ?? '-', $this->number($line->emissionsCo2eq));

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

            array_push($row, $this->number($line->quantity), $line->factorCo2eq, $this->number($line->emissionsCo2eq), null !== $line->locationBasedEmissionsCo2eq ? $this->number($line->locationBasedEmissionsCo2eq) : '---');

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
                $this->translator->trans($exclusionLine->source->value, [], 'source', $locale->value),
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
                sprintf('%s %s', $this->number($activityLine->quantity), $activityLine->unit->value),
                $activityLine->factorCo2eq,
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

            array_push($row, $this->translator->trans($line->source->value, [], 'source', $locale->value), sprintf('%s %s', $this->number($line->quantity), $line->unit->value), $line->factorCo2eq, (string) $line->factorYear, $line->factorSource ?? '-', $this->number($line->emissionsCo2eq));

            $rows[] = $row;
        }

        return $rows;
    }
}
