<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreCatalogItem;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreUnitParser;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\NameNormalizer;
use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleFilter;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleTranslation;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerFilter;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGas;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasFilter;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransport;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportFilter;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportTranslation;
use App\CarbonFootprint\Domain\Model\Unit;
use Symfony\Component\Uid\Uuid;

final class CatalogTargetFinder
{
    public function __construct(
        private CombustibleRepository $combustibleRepository,
        private RoadTransportRepository $roadTransportRepository,
        private RefrigerantGasRepository $refrigerantGasRepository,
        private ElectricityMarketerRepository $electricityMarketerRepository,
    ) {}

    /** @return list<CatalogTarget> */
    public function all(BioscoreCatalog $catalog): array
    {
        $targets = match ($catalog) {
            BioscoreCatalog::FacilityFuel => $this->combustibles(),
            BioscoreCatalog::TransportFuel => $this->roadTransports(),
            BioscoreCatalog::RefrigerantGas => $this->refrigerantGases(),
            BioscoreCatalog::ElectricityMarketer => $this->electricityMarketers(),
        };

        usort($targets, static function (CatalogTarget $a, CatalogTarget $b): int {
            $comparison = strcasecmp($a->name, $b->name);

            if (0 !== $comparison) {
                return $comparison;
            }

            return strcmp(null !== $a->vehicle ? $a->vehicle->value : '', null !== $b->vehicle ? $b->vehicle->value : '');
        });

        return $targets;
    }

    public function find(BioscoreCatalog $catalog, string $targetId): ?CatalogTarget
    {
        if (!Uuid::isValid($targetId)) {
            return null;
        }

        return match ($catalog) {
            BioscoreCatalog::FacilityFuel => $this->fromCombustible($this->combustibleRepository->find(new CombustibleId($targetId))),
            BioscoreCatalog::TransportFuel => $this->fromRoadTransport($this->roadTransportRepository->find(new RoadTransportId($targetId))),
            BioscoreCatalog::RefrigerantGas => $this->fromRefrigerantGas($this->refrigerantGasRepository->find(new RefrigerantGasId($targetId))),
            BioscoreCatalog::ElectricityMarketer => $this->fromElectricityMarketer($this->electricityMarketerRepository->find(new ElectricityMarketerId($targetId))),
        };
    }

    /** @return list<CatalogTarget> */
    public function matching(BioscoreCatalog $catalog, string $name, string $rawUnit): array
    {
        $normalizedName = NameNormalizer::normalize($name);
        $candidates = [];

        foreach ($this->all($catalog) as $target) {
            if (!BioscoreUnitParser::isCompatible($rawUnit, $target->unit)) {
                continue;
            }

            foreach ($target->names as $targetName) {
                if (NameNormalizer::normalize($targetName) === $normalizedName) {
                    $candidates[] = $target;

                    break;
                }
            }
        }

        return $candidates;
    }

    /** @return list<CatalogTarget> */
    private function combustibles(): array
    {
        $targets = [];

        foreach ($this->combustibleRepository->findByFilter(new CombustibleFilter(limit: 0)) as $combustible) {
            $targets[] = $this->fromCombustible($combustible);
        }

        return array_values(array_filter($targets));
    }

    /** @return list<CatalogTarget> */
    private function roadTransports(): array
    {
        $targets = [];

        foreach ($this->roadTransportRepository->findByFilter(new RoadTransportFilter(limit: 0)) as $roadTransport) {
            $targets[] = $this->fromRoadTransport($roadTransport);
        }

        return array_values(array_filter($targets));
    }

    /** @return list<CatalogTarget> */
    private function refrigerantGases(): array
    {
        $targets = [];

        foreach ($this->refrigerantGasRepository->findByFilter(new RefrigerantGasFilter(limit: 0)) as $refrigerantGas) {
            $targets[] = $this->fromRefrigerantGas($refrigerantGas);
        }

        return array_values(array_filter($targets));
    }

    /** @return list<CatalogTarget> */
    private function electricityMarketers(): array
    {
        $targets = [];

        foreach ($this->electricityMarketerRepository->findByFilter(new ElectricityMarketerFilter(limit: 0)) as $electricityMarketer) {
            $targets[] = $this->fromElectricityMarketer($electricityMarketer);
        }

        return array_values(array_filter($targets));
    }

    private function fromCombustible(?Combustible $combustible): ?CatalogTarget
    {
        if (null === $combustible) {
            return null;
        }

        $names = [$combustible->name()];

        foreach ($combustible->translations() as $translation) {
            assert($translation instanceof CombustibleTranslation);

            $names[] = $translation->name();
        }

        return new CatalogTarget(
            id: (string) $combustible->id(),
            name: $combustible->name(),
            names: array_values(array_unique($names)),
            unit: $combustible->unit(),
        );
    }

    private function fromRoadTransport(?RoadTransport $roadTransport): ?CatalogTarget
    {
        if (null === $roadTransport) {
            return null;
        }

        $names = [$roadTransport->name()];

        foreach ($roadTransport->translations() as $translation) {
            assert($translation instanceof RoadTransportTranslation);

            $names[] = $translation->name();
        }

        return new CatalogTarget(
            id: (string) $roadTransport->id(),
            name: $roadTransport->name(),
            names: array_values(array_unique($names)),
            unit: $roadTransport->unit(),
            vehicle: $roadTransport->vehicle(),
        );
    }

    private function fromRefrigerantGas(?RefrigerantGas $refrigerantGas): ?CatalogTarget
    {
        if (null === $refrigerantGas) {
            return null;
        }

        return new CatalogTarget(
            id: (string) $refrigerantGas->id(),
            name: $refrigerantGas->name(),
            names: [$refrigerantGas->name()],
            unit: $refrigerantGas->unit(),
        );
    }

    private function fromElectricityMarketer(?ElectricityMarketer $electricityMarketer): ?CatalogTarget
    {
        if (null === $electricityMarketer) {
            return null;
        }

        return new CatalogTarget(
            id: (string) $electricityMarketer->id(),
            name: $electricityMarketer->name(),
            names: [$electricityMarketer->name()],
            unit: Unit::KWh,
        );
    }
}
