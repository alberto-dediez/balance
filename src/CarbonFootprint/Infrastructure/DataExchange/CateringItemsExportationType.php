<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\CateringItem\CateringItemAuthorizer;
use App\CarbonFootprint\Application\CateringItem\Export\CateringItemsExportFilter;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItem;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemCategory;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemEmissionFactor;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\AdminCateringItemsExportType;
use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Translation\TranslatableMessage;

class CateringItemsExportationType implements ExportationType
{
    public function __construct(
        private readonly CateringItemRepository $repository,
        private readonly CateringItemAuthorizer $authorizer,
        private readonly EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function code(): string
    {
        return 'catering_items';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Catering');
    }

    public function returnRoute(): string
    {
        return 'admin_catering_items';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('cateringItem', new TranslatableMessage('Identificador'), selectedByDefault: false),
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
            new ExportColumnDefinition('emissionFactorGroup', new TranslatableMessage('Grupo')),
        ];
    }

    public function filterFormClass(): string
    {
        return AdminCateringItemsExportType::class;
    }

    public function createFilter(): object
    {
        return new CateringItemsExportFilter();
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof CateringItemsExportFilter) {
            return [];
        }

        return [
            'name' => $filter->name,
            'emissionFactorGroup' => $filter->emissionFactorGroup,
            'category' => $filter->category?->value,
            'orderBy' => $filter->orderBy,
            'orderWay' => $filter->orderWay,
        ];
    }

    public function hydrateFilter(array $criteria): object
    {
        $filter = new CateringItemsExportFilter();

        $filter->name = isset($criteria['name']) ? (string) $criteria['name'] : null;
        $filter->emissionFactorGroup = isset($criteria['emissionFactorGroup']) ? (string) $criteria['emissionFactorGroup'] : null;
        $filter->category = isset($criteria['category'])
            ? CateringItemCategory::from((string) $criteria['category'])
            : null;
        $filter->orderBy = isset($criteria['orderBy']) ? (string) $criteria['orderBy'] : 'default';
        $filter->orderWay = isset($criteria['orderWay']) ? (string) $criteria['orderWay'] : 'ASC';
        $filter->limit = 0;

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof CateringItemsExportFilter) {
            return;
        }

        $filter->limit = 0;
        $this->groupNameCache = [];

        foreach ($this->repository->findByFilter($filter) as $cateringItem) {
            $emissionFactors = $cateringItem->emissionFactors();

            if (0 === $emissionFactors->count()) {
                yield $this->baseRow($cateringItem) + $this->emptyFactorRow();

                continue;
            }

            foreach ($emissionFactors as $cateringItemEmissionFactor) {
                if (null !== $filter->emissionFactorGroup
                    && (string) $cateringItemEmissionFactor->emissionFactorGroupId() !== $filter->emissionFactorGroup
                ) {
                    continue;
                }

                yield $this->baseRow($cateringItem) + $this->factorRow($cateringItemEmissionFactor);
            }
        }
    }

    public function isAuthorized(User $user): bool
    {
        return $this->authorizer->canDoThat(
            new CateringItem(id: CateringItemId::generate()),
            Action::Export,
            $user,
        );
    }

    /** @return array<string, scalar|null> */
    private function baseRow(CateringItem $cateringItem): array
    {
        return [
            'cateringItem' => (string) $cateringItem->id(),
            'name' => $cateringItem->name(),
            'category' => $cateringItem->category()->value,
            'unit' => $cateringItem->unit()->value,
            'unitWeight' => $cateringItem->unitWeight() ?? '',
        ];
    }

    /** @return array<string, scalar|null> */
    private function factorRow(CateringItemEmissionFactor $cateringItemEmissionFactor): array
    {
        return [
            'emissionFactorGroup' => $this->groupName($cateringItemEmissionFactor->emissionFactorGroupId()),
            'year' => $cateringItemEmissionFactor->newFactor()->year(),
            'co2eq' => $cateringItemEmissionFactor->newFactor()->co2eq(),
            'co2' => $cateringItemEmissionFactor->newFactor()->co2(),
            'ch4' => $cateringItemEmissionFactor->newFactor()->ch4(),
            'n2o' => $cateringItemEmissionFactor->newFactor()->n2o(),
            'recycledCo2eq' => $cateringItemEmissionFactor->recycledFactor()->co2eq(),
            'recycledCo2' => $cateringItemEmissionFactor->recycledFactor()->co2(),
            'recycledCh4' => $cateringItemEmissionFactor->recycledFactor()->ch4(),
            'recycledN2o' => $cateringItemEmissionFactor->recycledFactor()->n2o(),
            'source' => $cateringItemEmissionFactor->newFactor()->source() ?? '',
        ];
    }

    /** @return array<string, scalar|null> */
    private function emptyFactorRow(): array
    {
        return [
            'emissionFactorGroup' => '',
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

    /** @var array<string, string> */
    private array $groupNameCache = [];

    private function groupName(EmissionFactorGroupId $emissionFactorGroupId): string
    {
        $key = (string) $emissionFactorGroupId;

        if (!isset($this->groupNameCache[$key])) {
            $group = $this->emissionFactorGroupRepository->find($emissionFactorGroupId);
            $this->groupNameCache[$key] = null !== $group ? $group->name() : '';
        }

        return $this->groupNameCache[$key];
    }
}
