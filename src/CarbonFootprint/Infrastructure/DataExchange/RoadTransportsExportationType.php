<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\RoadTransport\Export\RoadTransportsExportFilter;
use App\CarbonFootprint\Application\RoadTransport\RoadTransportAuthorizer;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransport;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportEmissionFactor;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\AdminRoadTransportsExportType;
use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Translation\TranslatableMessage;

class RoadTransportsExportationType implements ExportationType
{
    public function __construct(
        private readonly RoadTransportRepository $repository,
        private readonly RoadTransportAuthorizer $authorizer,
    ) {}

    public function code(): string
    {
        return 'road_transports';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Transporte por carretera');
    }

    public function returnRoute(): string
    {
        return 'admin_road_transports';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('road_transport', new TranslatableMessage('Identificador'), selectedByDefault: false),
            new ExportColumnDefinition('name', new TranslatableMessage('Nombre')),
            new ExportColumnDefinition('unit', new TranslatableMessage('Unidad')),
            new ExportColumnDefinition('vehicle', new TranslatableMessage('Tipo de vehículo')),
            new ExportColumnDefinition('year', new TranslatableMessage('Año')),
            new ExportColumnDefinition('co2eq', new TranslatableMessage('CO2eq')),
            new ExportColumnDefinition('co2', new TranslatableMessage('CO2')),
            new ExportColumnDefinition('ch4', new TranslatableMessage('CH4')),
            new ExportColumnDefinition('n2o', new TranslatableMessage('N2O')),
            new ExportColumnDefinition('source', new TranslatableMessage('Fuente')),
        ];
    }

    public function filterFormClass(): string
    {
        return AdminRoadTransportsExportType::class;
    }

    public function createFilter(): object
    {
        return new RoadTransportsExportFilter();
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof RoadTransportsExportFilter) {
            return [];
        }

        return [
            'name' => $filter->name,
            'orderBy' => $filter->orderBy,
            'orderWay' => $filter->orderWay,
        ];
    }

    public function hydrateFilter(array $criteria): object
    {
        $filter = new RoadTransportsExportFilter();

        $filter->name = isset($criteria['name']) ? (string) $criteria['name'] : null;
        $filter->orderBy = isset($criteria['orderBy']) ? (string) $criteria['orderBy'] : 'default';
        $filter->orderWay = isset($criteria['orderWay']) ? (string) $criteria['orderWay'] : 'ASC';
        $filter->limit = 0;

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof RoadTransportsExportFilter) {
            return;
        }

        $filter->limit = 0;

        foreach ($this->repository->findByFilter($filter) as $roadTransport) {
            $emissionFactors = $roadTransport->emissionFactors();

            if (0 === $emissionFactors->count()) {
                yield $this->baseRow($roadTransport) + $this->emptyFactorRow();

                continue;
            }

            foreach ($emissionFactors as $roadTransportEmissionFactor) {
                yield $this->baseRow($roadTransport) + $this->factorRow($roadTransportEmissionFactor);
            }
        }
    }

    public function isAuthorized(User $user): bool
    {
        return $this->authorizer->canDoThat(
            new RoadTransport(id: RoadTransportId::generate()),
            Action::Export,
            $user,
        );
    }

    /** @return array<string, scalar|null> */
    private function baseRow(RoadTransport $roadTransport): array
    {
        return [
            'road_transport' => (string) $roadTransport->id(),
            'name' => $roadTransport->name(),
            'unit' => $roadTransport->unit()->value,
            'vehicle' => $roadTransport->vehicle()->value,
        ];
    }

    /** @return array<string, scalar|null> */
    private function factorRow(RoadTransportEmissionFactor $roadTransportEmissionFactor): array
    {
        $emissionFactor = $roadTransportEmissionFactor->emissionFactor();

        return [
            'year' => $emissionFactor->year(),
            'co2eq' => $emissionFactor->co2eq(),
            'co2' => $emissionFactor->co2(),
            'ch4' => $emissionFactor->ch4(),
            'n2o' => $emissionFactor->n2o(),
            'source' => $emissionFactor->source() ?? '',
        ];
    }

    /** @return array<string, scalar|null> */
    private function emptyFactorRow(): array
    {
        return [
            'year' => '',
            'co2eq' => '',
            'co2' => '',
            'ch4' => '',
            'n2o' => '',
            'source' => '',
        ];
    }
}
