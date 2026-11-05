<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint\Export;

use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprintReport\Data\ActivityItemNameResolver;
use App\CarbonFootprint\Application\CarbonFootprintReport\Data\ProviderNameResolver;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\CountedActivityReferences;
use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\CapitalGoodActivity;
use App\CarbonFootprint\Domain\Model\Activity\CateringItemActivity;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivity;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Activity\MachineryFuelActivity;
use App\CarbonFootprint\Domain\Model\Activity\MaterialActivity;
use App\CarbonFootprint\Domain\Model\Activity\MerchandisingItemActivity;
use App\CarbonFootprint\Domain\Model\Activity\OvernightStayActivity;
use App\CarbonFootprint\Domain\Model\Activity\RefrigerantGasActivity;
use App\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivity;
use App\CarbonFootprint\Domain\Model\Activity\TransportActivity;
use App\CarbonFootprint\Domain\Model\Activity\WasteActivity;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecision;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\Unit;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Domain\Model\User\UserRepository;

final class CarbonFootprintDetailRowsBuilder
{
    /** @var array<string, string> */
    private array $propertyNamesById = [];

    public function __construct(
        private ActivityRepository $activityRepository,
        private CountedActivityReferences $countedActivityReferences,
        private ActivityItemNameResolver $itemNameResolver,
        private DerivedSourceEmissionsCalculator $derivedSourceEmissionsCalculator,
        private ProviderNameResolver $providerNameResolver,
        private PropertyRepository $propertyRepository,
        private UserRepository $userRepository,
    ) {}

    /** @return list<CarbonFootprintDetailRow> */
    public function build(CarbonFootprint $carbonFootprint): array
    {
        $this->providerNameResolver->reset();
        $this->propertyNamesById = [];

        $year = $carbonFootprint->calculationYear();
        $emissionFactorGroupId = $carbonFootprint->emissionFactorGroupId();

        $rows = [];

        foreach ($this->countedActivityReferences->of($carbonFootprint) as $reference) {
            $activity = $this->activityRepository->findById($reference->activityId);

            if (null === $activity) {
                continue;
            }

            $rows[] = $this->activityRow($carbonFootprint, $activity, $reference->placement, $year, $emissionFactorGroupId);
        }

        if (!$carbonFootprint instanceof EventCarbonFootprint) {
            foreach ($this->derivedSourceEmissionsCalculator->lines($carbonFootprint, Scope::Scope3) as $line) {
                $origin = $this->activityRepository->findById($line->activityId);

                $rows[] = new CarbonFootprintDetailRow(
                    ...$this->materialityFieldsFor($carbonFootprint, Scope::Scope3, $line->source),
                    rowType: CarbonFootprintDetailRowType::DerivedFuelAndEnergy,
                    stage: Scope::Scope3,
                    source: $line->source,
                    propertyName: $this->propertyNameOf($line->origin->propertyId),
                    activityType: $line->activityType,
                    itemName: null !== $origin ? $this->itemNameResolver->resolve($origin, $year, $emissionFactorGroupId) : null,
                    providerName: $this->providerNameOf($line->origin->providerId),
                    fromEmployees: $line->origin->fromEmployees,
                    emissionsCo2eq: $line->emissions->co2eq(),
                    emissionsCo2: $line->emissions->co2(),
                    emissionsCh4: $line->emissions->ch4InKilograms(),
                    emissionsN2o: $line->emissions->n2oInKilograms(),
                    activityId: (string) $line->activityId,
                );
            }
        }

        if ($carbonFootprint instanceof CompanyCarbonFootprint) {
            foreach ($this->derivedSourceEmissionsCalculator->nonConsolidatedPropertyLines($carbonFootprint, Scope::Scope3) as $line) {
                $propertyName = $this->propertyNameOf($line->propertyId);

                $rows[] = new CarbonFootprintDetailRow(
                    ...$this->materialityFieldsFor($carbonFootprint, Scope::Scope3, $line->source),
                    rowType: CarbonFootprintDetailRowType::NonConsolidatedProperty,
                    stage: Scope::Scope3,
                    source: $line->source,
                    propertyName: $propertyName,
                    itemName: $propertyName,
                    emissionsCo2eq: $line->emissions->co2eq(),
                    emissionsCo2: $line->emissions->co2(),
                    emissionsCh4: $line->emissions->ch4InKilograms(),
                    emissionsN2o: $line->emissions->n2oInKilograms(),
                    manualCo2eq: $line->manualCo2eq,
                );
            }
        }

        if ($carbonFootprint->isStageExcluded(Scope::Scope3)) {
            $rows[] = new CarbonFootprintDetailRow(
                rowType: CarbonFootprintDetailRowType::Exclusion,
                stage: Scope::Scope3,
                exclusionReason: $carbonFootprint->stageExclusionReason(Scope::Scope3),
                exclusionComment: $carbonFootprint->stageExclusionComment(Scope::Scope3),
            );
        }

        foreach ($carbonFootprint->allExclusions() as $exclusion) {
            $rows[] = new CarbonFootprintDetailRow(
                ...$this->materialityFieldsFor($carbonFootprint, $exclusion->stage(), $exclusion->source()),
                rowType: CarbonFootprintDetailRowType::Exclusion,
                stage: $exclusion->stage(),
                source: $exclusion->source(),
                exclusionReason: $exclusion->reason(),
                exclusionComment: $exclusion->comment(),
            );
        }

        usort($rows, static fn(CarbonFootprintDetailRow $a, CarbonFootprintDetailRow $b): int => self::sortKey($a) <=> self::sortKey($b));

        return $rows;
    }

    /** @return array{materialityScorePercent: ?float, materialityThresholdPercent: ?int, materialityVerdict: ?MaterialityVerdict, materialityForcedByHighMagnitude: ?bool, materialityOverride: ?string} */
    private function materialityFieldsFor(CarbonFootprint $carbonFootprint, CalculationStage $stage, ?Source $source): array
    {
        $decision = Scope::Scope3 === $stage && null !== $source ? $carbonFootprint->materialityDecisionFor($source) : null;

        return [
            'materialityScorePercent' => $decision?->scorePercent(),
            'materialityThresholdPercent' => $decision?->thresholdPercent(),
            'materialityVerdict' => $decision?->verdict(),
            'materialityForcedByHighMagnitude' => $decision?->isForcedByHighMagnitude(),
            'materialityOverride' => null === $decision ? null : $this->materialityOverrideOf($decision),
        ];
    }

    private function materialityOverrideOf(MaterialityDecision $decision): ?string
    {
        if (!$decision->isOverridden()) {
            return null;
        }

        $userId = $decision->overriddenByUserId();
        $user = null === $userId ? null : $this->userRepository->find($userId);

        return sprintf('%s · %s · %s', null === $user ? '---' : (string) $user, $decision->overriddenAt()?->format('d/m/Y') ?? '---', $decision->overrideJustification() ?? '');
    }

    private function activityRow(CarbonFootprint $carbonFootprint, Activity $activity, ActivityPlacement $placement, int $year, ?EmissionFactorGroupId $emissionFactorGroupId): CarbonFootprintDetailRow
    {
        $emissionFactor = $activity->emissionFactor();
        $emissions = $activity->emissions();
        $weight = $activity instanceof TransportActivity ? $activity->weight() : null;
        $containerCapacity = $activity instanceof WasteActivity ? $activity->containerCapacity() : null;

        return new CarbonFootprintDetailRow(
            ...$this->materialityFieldsFor($carbonFootprint, $placement->stage, $placement->source),
            rowType: CarbonFootprintDetailRowType::Activity,
            stage: $placement->stage,
            source: $placement->source,
            propertyName: $this->propertyNameOf($placement->propertyId),
            activityType: ActivityType::fromActivity($activity),
            itemName: $this->itemNameResolver->resolve($activity, $year, $emissionFactorGroupId),
            providerName: $this->providerNameOf($placement->providerId),
            fromEmployees: $placement->fromEmployees,
            dataSource: $this->dataSourceOf($activity),
            quantity: $activity->quantity()->value(),
            unit: $activity->quantity()->unit(),
            condition: $this->conditionOf($activity),
            nights: $activity instanceof OvernightStayActivity ? $activity->nights() : null,
            people: $activity instanceof OvernightStayActivity ? $activity->people() : null,
            weightTonnes: $weight?->convertTo(Unit::Tonne)->value(),
            distanceKm: $activity instanceof TransportActivity ? $activity->distanceKm() : null,
            containerCapacityLitres: $containerCapacity?->convertTo(Unit::Liter)->value(),
            fillPercentage: $activity instanceof WasteActivity ? $activity->fillPercentage() : null,
            acquisitionYear: $activity instanceof CapitalGoodActivity ? $activity->acquisitionYear() : null,
            factorCo2eq: $emissionFactor->co2eq(),
            factorUnit: $emissionFactor->unit(),
            factorYear: $emissionFactor->year(),
            factorSource: $emissionFactor->source(),
            emissionsCo2eq: $emissions->co2eq(),
            emissionsCo2: $emissions->co2(),
            emissionsCh4: $emissions->ch4InKilograms(),
            emissionsN2o: $emissions->n2oInKilograms(),
            wellToTankCo2eq: $this->exportsWellToTank($carbonFootprint, $placement) ? $this->wellToTankEmissionsOf($activity)?->co2eq() : null,
            locationBasedCo2eq: $activity instanceof ElectricityActivity ? $activity->locationBasedEmissions()?->co2eq() : null,
            registeredAt: $activity->registeredAt(),
            updatedAt: $activity->updatedAt(),
            activityId: (string) $activity->id(),
        );
    }

    private function exportsWellToTank(CarbonFootprint $carbonFootprint, ActivityPlacement $placement): bool
    {
        return $carbonFootprint instanceof EventCarbonFootprint
            || !$placement->stage instanceof Scope
            || $this->derivedSourceEmissionsCalculator->isDerivedSourceCalculated($carbonFootprint, Scope::Scope3);
    }

    private function dataSourceOf(Activity $activity): ?string
    {
        return match (true) {
            $activity instanceof ReportedEmissionsActivity => $activity->description(),
            $activity instanceof CombustibleActivity,
            $activity instanceof MachineryFuelActivity,
            $activity instanceof ElectricityActivity,
            $activity instanceof OvernightStayActivity,
            $activity instanceof TransportActivity,
            $activity instanceof WasteActivity,
            $activity instanceof RefrigerantGasActivity => $activity->source(),
            default => null,
        };
    }

    private function conditionOf(Activity $activity): ?ItemCondition
    {
        return match (true) {
            $activity instanceof MaterialActivity,
            $activity instanceof CateringItemActivity,
            $activity instanceof MerchandisingItemActivity => $activity->condition(),
            default => null,
        };
    }

    private function wellToTankEmissionsOf(Activity $activity): ?Emissions
    {
        return match (true) {
            $activity instanceof CombustibleActivity,
            $activity instanceof MachineryFuelActivity,
            $activity instanceof ElectricityActivity => $activity->wellToTankEmissions(),
            default => null,
        };
    }

    private function providerNameOf(?ProviderId $providerId): ?string
    {
        if (null === $providerId) {
            return null;
        }

        return $this->providerNameResolver->resolve($providerId);
    }

    private function propertyNameOf(?PropertyId $propertyId): ?string
    {
        if (null === $propertyId) {
            return null;
        }

        $key = (string) $propertyId;

        if (!isset($this->propertyNamesById[$key])) {
            $this->propertyNamesById[$key] = $this->propertyRepository->find($propertyId)?->name() ?? $key;
        }

        return $this->propertyNamesById[$key];
    }

    /** @return array{int, int, int, int, string, string} */
    private static function sortKey(CarbonFootprintDetailRow $row): array
    {
        return [
            CarbonFootprintDetailRowType::Exclusion === $row->rowType ? 1 : 0,
            self::stagePosition($row->stage),
            (int) array_search($row->rowType, CarbonFootprintDetailRowType::cases(), true),
            null === $row->source ? -1 : (int) array_search($row->source, Source::cases(), true),
            $row->propertyName ?? '',
            $row->itemName ?? '',
        ];
    }

    private static function stagePosition(CalculationStage $stage): int
    {
        return match (true) {
            $stage instanceof Scope => $stage->stepNumber(),
            $stage instanceof Phase => (int) array_search($stage, Phase::cases(), true),
            default => 0,
        };
    }
}
