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
            new ExportColumnDefinition('co2eq', new TranslatableMessage('CO2eq')),
            new ExportColumnDefinition('co2', new TranslatableMessage('CO2')),
            new ExportColumnDefinition('ch4', new TranslatableMessage('CH4')),
            new ExportColumnDefinition('n2o', new TranslatableMessage('N2O')),
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
        $emissionFactor = $materialEmissionFactor->emissionFactor();

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
