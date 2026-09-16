<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SourceExclusion;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Property\Domain\Model\Property\Property;
use Symfony\Contracts\Translation\TranslatorInterface;

final class OrganizationalResultsView
{
    public function __construct(private TranslatorInterface $translator) {}

    /**
     * @param array<string, Emissions>                $emissionsBySource
     * @param array<string, Emissions>                $emissionsByScope
     * @param array<string, array<string, Emissions>> $emissionsByScopeAndSource
     * @param list<SourceExclusion>                   $exclusions
     * @param array<string, Emissions>                $emissionsByPropertyId
     * @param list<Property>                          $properties
     *
     * @return array<string, mixed>
     */
    public function build(
        array $emissionsBySource,
        array $emissionsByScope,
        array $emissionsByScopeAndSource,
        array $exclusions,
        array $emissionsByPropertyId = [],
        array $properties = [],
    ): array {
        return [
            'sourceChart' => $this->sourceChart($emissionsBySource),
            'scopeChart' => $this->scopeChart($emissionsByScope),
            'scopeCategories' => array_map(
                fn(Scope $scope) => $this->translator->trans($scope->value, [], 'scope'),
                Scope::cases(),
            ),
            'scopeSourceSeries' => $this->scopeSourceSeries($emissionsBySource, $emissionsByScopeAndSource),
            'exclusions' => $this->exclusions($exclusions),
            'propertyChart' => $this->propertyChart($emissionsByPropertyId, $properties),
        ];
    }

    /**
     * @param array<string, Emissions> $emissionsBySource
     *
     * @return array{labels: list<string>, values: list<float>}
     */
    private function sourceChart(array $emissionsBySource): array
    {
        $labels = [];
        $values = [];

        foreach (Source::cases() as $source) {
            $co2eq = (float) ($emissionsBySource[$source->value] ?? new Emissions())->co2eq();

            if ($co2eq <= 0.0) {
                continue;
            }

            $labels[] = $this->translator->trans($source->value, [], 'source');
            $values[] = $co2eq;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @param array<string, Emissions> $emissionsByScope
     *
     * @return array{labels: list<string>, values: list<float>}
     */
    private function scopeChart(array $emissionsByScope): array
    {
        $labels = [];
        $values = [];

        foreach (Scope::cases() as $scope) {
            $labels[] = $this->translator->trans($scope->value, [], 'scope');
            $values[] = (float) ($emissionsByScope[$scope->value] ?? new Emissions())->co2eq();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @param array<string, Emissions>                $emissionsBySource
     * @param array<string, array<string, Emissions>> $emissionsByScopeAndSource
     *
     * @return list<array{name: string, data: list<float>}>
     */
    private function scopeSourceSeries(array $emissionsBySource, array $emissionsByScopeAndSource): array
    {
        $series = [];

        foreach (Source::cases() as $source) {
            $totalForSource = (float) ($emissionsBySource[$source->value] ?? new Emissions())->co2eq();

            if ($totalForSource <= 0.0) {
                continue;
            }

            $data = [];
            foreach (Scope::cases() as $scope) {
                $data[] = (float) ($emissionsByScopeAndSource[$scope->value][$source->value] ?? new Emissions())->co2eq();
            }

            $series[] = [
                'name' => $this->translator->trans($source->value, [], 'source'),
                'data' => $data,
            ];
        }

        return $series;
    }

    /**
     * @param list<SourceExclusion> $exclusions
     *
     * @return list<array{scope: string, source: string, reason: string, comment: ?string}>
     */
    private function exclusions(array $exclusions): array
    {
        $rows = [];

        foreach ($exclusions as $exclusion) {
            $rows[] = [
                'scope' => $this->translator->trans($exclusion->stage()->key(), [], 'scope'),
                'source' => $this->translator->trans($exclusion->source()->value, [], 'source'),
                'reason' => $this->translator->trans($exclusion->reason()->value, [], 'exclusionReason'),
                'comment' => $exclusion->comment(),
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, Emissions> $emissionsByPropertyId
     * @param list<Property>           $properties
     *
     * @return array{labels: list<string>, values: list<float>}
     */
    private function propertyChart(array $emissionsByPropertyId, array $properties): array
    {
        if ([] === $emissionsByPropertyId) {
            return ['labels' => [], 'values' => []];
        }

        $names = [];
        foreach ($properties as $property) {
            $names[(string) $property->id()] = (string) $property->name();
        }

        $labels = [];
        $values = [];

        foreach ($emissionsByPropertyId as $propertyId => $emissions) {
            $labels[] = '' === $propertyId
                ? $this->translator->trans('Sin asignar a propiedad')
                : ($names[$propertyId] ?? $propertyId);
            $values[] = (float) $emissions->co2eq();
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
