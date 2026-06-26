<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\Material\Export\MaterialsExportFilter;
use App\CarbonFootprint\Application\Material\MaterialAuthorizer;
use App\CarbonFootprint\Domain\Model\Material\Material;
use App\CarbonFootprint\Domain\Model\Material\MaterialCategory;
use App\CarbonFootprint\Domain\Model\Material\MaterialEmissionFactor;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\AdminMaterialsExportType;
use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Translation\TranslatableMessage;

class MaterialsExportationType implements ExportationType
{
    public function __construct(
        private readonly MaterialRepository $repository,
        private readonly MaterialAuthorizer $authorizer,
    ) {}

    public function code(): string
    {
        return 'materials';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Materiales');
    }

    public function returnRoute(): string
    {
        return 'admin_materials';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('material', new TranslatableMessage('Identificador'), selectedByDefault: false),
            new ExportColumnDefinition('name', new TranslatableMessage('Nombre')),
            new ExportColumnDefinition('category', new TranslatableMessage('Categoría')),
            new ExportColumnDefinition('unit', new TranslatableMessage('Unidad')),
            new ExportColumnDefinition('unitWeight', new TranslatableMessage('Peso por unidad (kg)')),
            new ExportColumnDefinition('year', new TranslatableMessage('Año')),
            new ExportColumnDefinition('co2eq', new TranslatableMessage('CO2eq nueva fabricación')),
            new ExportColumnDefinition('co2', new TranslatableMessage('CO2 nueva fabricación')),
            new ExportColumnDefinition('ch4', new TranslatableMessage('CH4 nueva fabricación')),
            new ExportColumnDefinition('n2o', new TranslatableMessage('N2O nueva fabricación')),
            new ExportColumnDefinition('recycledCo2eq', new TranslatableMessage('CO2eq reciclado')),
            new ExportColumnDefinition('recycledCo2', new TranslatableMessage('CO2 reciclado')),
            new ExportColumnDefinition('recycledCh4', new TranslatableMessage('CH4 reciclado')),
            new ExportColumnDefinition('recycledN2o', new TranslatableMessage('N2O reciclado')),
            new ExportColumnDefinition('source', new TranslatableMessage('Fuente')),
        ];
    }

    public function filterFormClass(): string
    {
        return AdminMaterialsExportType::class;
    }

    public function createFilter(): object
    {
        return new MaterialsExportFilter();
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof MaterialsExportFilter) {
            return [];
        }

        return [
            'name' => $filter->name,
            'category' => $filter->category?->value,
            'orderBy' => $filter->orderBy,
            'orderWay' => $filter->orderWay,
        ];
    }

    public function hydrateFilter(array $criteria): object
    {
        $filter = new MaterialsExportFilter();

        $filter->name = isset($criteria['name']) ? (string) $criteria['name'] : null;
        $filter->category = isset($criteria['category'])
            ? MaterialCategory::from((string) $criteria['category'])
            : null;
        $filter->orderBy = isset($criteria['orderBy']) ? (string) $criteria['orderBy'] : 'default';
        $filter->orderWay = isset($criteria['orderWay']) ? (string) $criteria['orderWay'] : 'ASC';
        $filter->limit = 0;

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof MaterialsExportFilter) {
            return;
        }

        $filter->limit = 0;

        foreach ($this->repository->findByFilter($filter) as $material) {
            $emissionFactors = $material->emissionFactors();

            if (0 === $emissionFactors->count()) {
                yield $this->baseRow($material) + $this->emptyFactorRow();

                continue;
            }

            foreach ($emissionFactors as $materialEmissionFactor) {
                yield $this->baseRow($material) + $this->factorRow($materialEmissionFactor);
            }
        }
    }

    public function isAuthorized(User $user): bool
    {
        return $this->authorizer->canDoThat(
            new Material(id: MaterialId::generate()),
            Action::Export,
            $user,
        );
    }

    /** @return array<string, scalar|null> */
    private function baseRow(Material $material): array
    {
        return [
            'material' => (string) $material->id(),
            'name' => $material->name(),
            'category' => $material->category()->value,
            'unit' => $material->unit()->value,
            'unitWeight' => $material->unitWeight() ?? '',
        ];
    }

    /** @return array<string, scalar|null> */
    private function factorRow(MaterialEmissionFactor $materialEmissionFactor): array
    {
        return [
            'year' => $materialEmissionFactor->newFactor()->year(),
            'co2eq' => $materialEmissionFactor->newFactor()->co2eq(),
            'co2' => $materialEmissionFactor->newFactor()->co2(),
            'ch4' => $materialEmissionFactor->newFactor()->ch4(),
            'n2o' => $materialEmissionFactor->newFactor()->n2o(),
            'recycledCo2eq' => $materialEmissionFactor->recycledFactor()->co2eq(),
            'recycledCo2' => $materialEmissionFactor->recycledFactor()->co2(),
            'recycledCh4' => $materialEmissionFactor->recycledFactor()->ch4(),
            'recycledN2o' => $materialEmissionFactor->recycledFactor()->n2o(),
            'source' => $materialEmissionFactor->newFactor()->source() ?? '',
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
            'recycledCo2eq' => '',
            'recycledCo2' => '',
            'recycledCh4' => '',
            'recycledN2o' => '',
            'source' => '',
        ];
    }
}
