<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OrganizationalCarbonFootprint\View;

use App\CarbonFootprint\Application\CarbonFootprint\StageExclusionAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecision;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SourceExclusion;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Property\Domain\Model\Property\Property;
use App\Security\Domain\Model\User\UserId;
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
     * @param list<MaterialityDecision>               $materialityDecisions
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
        ?StageExclusionAnswer $scope3Exclusion = null,
        array $materialityDecisions = [],
        bool $pendingMaterialityReview = false,
    ): array {
        return [
            'sourceChart' => $this->sourceChart($emissionsBySource),
            'scopeChart' => $this->scopeChart($emissionsByScope),
            'scopeCategories' => array_map(
                fn(Scope $scope) => $this->translator->trans($scope->value, [], 'scope'),
                Scope::cases(),
            ),
            'scopeSourceSeries' => $this->scopeSourceSeries($emissionsBySource, $emissionsByScopeAndSource),
            'exclusions' => $this->exclusions($exclusions, $scope3Exclusion, $materialityDecisions),
            'pendingMaterialityReview' => $pendingMaterialityReview,
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
     * @param list<SourceExclusion>     $exclusions
     * @param list<MaterialityDecision> $materialityDecisions
     *
     * @return list<array{scope: string, source: string, reason: string, comment: ?string, origin: string, overriddenBy: ?UserId, overriddenAt: ?\DateTimeImmutable}>
     */
    private function exclusions(array $exclusions, ?StageExclusionAnswer $scope3Exclusion, array $materialityDecisions): array
    {
        $rows = [];

        if (null !== $scope3Exclusion) {
            $rows[] = [
                'scope' => $this->translator->trans(Scope::Scope3->value, [], 'scope'),
                'source' => $this->translator->trans('Alcance completo'),
                'reason' => $this->translator->trans($scope3Exclusion->reason->value, [], 'exclusionReason'),
                'comment' => $scope3Exclusion->comment,
                'origin' => $this->translator->trans('Manual'),
                'overriddenBy' => null,
                'overriddenAt' => null,
            ];
        }

        $decisionsBySource = [];
        foreach ($materialityDecisions as $decision) {
            $source = $decision->source();

            if (null !== $source) {
                $decisionsBySource[$source->value] = $decision;
            }
        }

        foreach ($exclusions as $exclusion) {
            $decision = $exclusion->stage()->key() === Scope::Scope3->key() ? ($decisionsBySource[$exclusion->source()->value] ?? null) : null;

            $rows[] = [
                'scope' => $this->translator->trans($exclusion->stage()->key(), [], 'scope'),
                'source' => $this->translator->trans($exclusion->source()->value, [], 'source'),
                'reason' => $this->translator->trans($exclusion->reason()->value, [], 'exclusionReason'),
                'comment' => $exclusion->comment(),
                'origin' => $this->originOf($decision),
                'overriddenBy' => true === $decision?->isOverridden() ? $decision->overriddenByUserId() : null,
                'overriddenAt' => true === $decision?->isOverridden() ? $decision->overriddenAt() : null,
            ];
        }

        return $rows;
    }

    private function originOf(?MaterialityDecision $decision): string
    {
        if (null === $decision) {
            return $this->translator->trans('Manual');
        }

        if ($decision->isOverridden()) {
            return $this->translator->trans('Anulación de la recomendación');
        }

        return $decision->recommendsExclusion()
            ? $this->translator->trans('Recomendación de materialidad')
            : $this->translator->trans('Manual');
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
