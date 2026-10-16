<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreConsumption;

use App\Bioscore\Application\BioscoreCatalogItem\CatalogTarget;
use App\Bioscore\Application\BioscoreCatalogItem\CatalogTargetFinder;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemRepository;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreUnitParser;
use App\Bioscore\Domain\Model\BioscoreConsumption\BioscoreConsumption;
use App\Bioscore\Domain\Model\BioscoreConsumption\BioscorePropertyConsumptions;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ActivityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\CombustibleAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ElectricityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\RefrigerantGasAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\TransportAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\Property\Domain\Model\Property\PropertyId;

final class BioscoreConsumptionAnswersBuilder
{
    private const string SOURCE = 'Bioscore';

    public function __construct(
        private BioscoreCatalogItemRepository $catalogItems,
        private CatalogTargetFinder $targetFinder,
    ) {}

    public function build(Scope $scope, BioscorePropertyConsumptions $consumptions): BioscoreConsumptionAnswers
    {
        /** @var array<string, list<ActivityAnswer>> $answersBySection */
        $answersBySection = [];
        $skipped = [];

        foreach ($this->classify($scope, $consumptions) as [$sectionKey, $catalog, $consumption]) {
            if ($consumption->consumption <= 0) {
                continue;
            }

            if (null === $catalog) {
                $skipped[] = $this->skip($consumption, BioscoreConsumptionSkipReason::Unmapped, $consumptions->propertyId);

                continue;
            }

            $item = $this->catalogItems->findByCatalogAndBioscoreId($catalog, $consumption->itemId);
            $targetId = $item?->targetId();

            if (null === $item || !$item->isMapped() || null === $targetId) {
                $skipped[] = $this->skip($consumption, BioscoreConsumptionSkipReason::Unmapped, $consumptions->propertyId);

                continue;
            }

            $target = $this->targetFinder->find($catalog, $targetId);

            if (null === $target) {
                $skipped[] = $this->skip($consumption, BioscoreConsumptionSkipReason::TargetMissing, $consumptions->propertyId);

                continue;
            }

            $parsedUnit = BioscoreUnitParser::parse($consumption->itemUnit);

            if (null !== $parsedUnit && $parsedUnit->magnitude() !== $target->unit->magnitude()) {
                $skipped[] = $this->skip($consumption, BioscoreConsumptionSkipReason::IncompatibleUnit, $consumptions->propertyId);

                continue;
            }

            $quantity = new Quantity($consumption->consumption, $parsedUnit ?? $target->unit);

            $answersBySection[$sectionKey][] = $this->answer($catalog, $target, $quantity);
        }

        $sectionAnswers = [];

        foreach ($scope->sections() as $section) {
            if (!isset($answersBySection[$section->key])) {
                continue;
            }

            $sectionAnswers[] = new SectionAnswer($section, $answersBySection[$section->key]);
        }

        return new BioscoreConsumptionAnswers($sectionAnswers, $skipped);
    }

    /** @return list<array{string, ?BioscoreCatalog, BioscoreConsumption}> */
    private function classify(Scope $scope, BioscorePropertyConsumptions $consumptions): array
    {
        $classified = [];

        if (Scope::Scope1 === $scope) {
            foreach ($consumptions->combustibles as $consumption) {
                if (true === $consumption->usableForFacilities) {
                    $classified[] = ['combustibles', BioscoreCatalog::FacilityFuel, $consumption];
                } elseif (true === $consumption->usableForTransportation) {
                    $classified[] = ['vehicles', BioscoreCatalog::TransportFuel, $consumption];
                } else {
                    $classified[] = ['combustibles', null, $consumption];
                }
            }

            foreach ($consumptions->refrigerantGases as $consumption) {
                $classified[] = ['refrigerantGases', BioscoreCatalog::RefrigerantGas, $consumption];
            }
        }

        if (Scope::Scope2 === $scope) {
            foreach ($consumptions->electricity as $consumption) {
                $classified[] = ['electricity', BioscoreCatalog::ElectricityMarketer, $consumption];
            }
        }

        return $classified;
    }

    private function answer(BioscoreCatalog $catalog, CatalogTarget $target, Quantity $quantity): ActivityAnswer
    {
        return match ($catalog) {
            BioscoreCatalog::FacilityFuel => new CombustibleAnswer(new CombustibleId($target->id), $quantity, source: self::SOURCE),
            BioscoreCatalog::TransportFuel => new TransportAnswer(new TransportId($target->id), $quantity, source: self::SOURCE),
            BioscoreCatalog::RefrigerantGas => new RefrigerantGasAnswer(new RefrigerantGasId($target->id), $quantity, source: self::SOURCE),
            BioscoreCatalog::ElectricityMarketer => new ElectricityAnswer(new ElectricityMarketerId($target->id), null, $quantity, source: self::SOURCE),
        };
    }

    private function skip(BioscoreConsumption $consumption, BioscoreConsumptionSkipReason $reason, PropertyId $propertyId): SkippedBioscoreConsumption
    {
        return new SkippedBioscoreConsumption(
            bioscoreId: $consumption->itemId,
            name: $consumption->itemName,
            consumption: $consumption->consumption,
            unit: $consumption->itemUnit,
            reason: $reason,
            propertyId: $propertyId,
        );
    }
}
