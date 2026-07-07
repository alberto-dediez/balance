<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\MerchandisingItem\Export\MerchandisingItemsExportFilter;
use App\CarbonFootprint\Application\MerchandisingItem\MerchandisingItemAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItem;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemCategory;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemEmissionFactor;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\AdminMerchandisingItemsExportType;
use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Translation\TranslatableMessage;

class MerchandisingItemsExportationType implements ExportationType
{
    public function __construct(
        private readonly MerchandisingItemRepository $repository,
        private readonly MerchandisingItemAuthorizer $authorizer,
        private readonly EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function code(): string
    {
        return 'merchandising_items';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Merchandising');
    }

    public function returnRoute(): string
    {
        return 'admin_merchandising_items';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('merchandisingItem', new TranslatableMessage('Identificador'), selectedByDefault: false),
            new ExportColumnDefinition('name', new TranslatableMessage('Nombre')),
            new ExportColumnDefinition('category', new TranslatableMessage('Categoría')),
            new ExportColumnDefinition('unit', new TranslatableMessage('Unidad')),
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
        return AdminMerchandisingItemsExportType::class;
    }

    public function createFilter(): object
    {
        return new MerchandisingItemsExportFilter();
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof MerchandisingItemsExportFilter) {
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
        $filter = new MerchandisingItemsExportFilter();

        $filter->name = isset($criteria['name']) ? (string) $criteria['name'] : null;
        $filter->emissionFactorGroup = isset($criteria['emissionFactorGroup']) ? (string) $criteria['emissionFactorGroup'] : null;
        $filter->category = isset($criteria['category'])
            ? MerchandisingItemCategory::from((string) $criteria['category'])
            : null;
        $filter->orderBy = isset($criteria['orderBy']) ? (string) $criteria['orderBy'] : 'default';
        $filter->orderWay = isset($criteria['orderWay']) ? (string) $criteria['orderWay'] : 'ASC';
        $filter->limit = 0;

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof MerchandisingItemsExportFilter) {
            return;
        }

        $filter->limit = 0;
        $this->groupNameCache = [];

        foreach ($this->repository->findByFilter($filter) as $merchandisingItem) {
            $emissionFactors = $merchandisingItem->emissionFactors();

            if (0 === $emissionFactors->count()) {
                yield $this->baseRow($merchandisingItem) + $this->emptyFactorRow();

                continue;
            }

            foreach ($emissionFactors as $merchandisingItemEmissionFactor) {
                if (null !== $filter->emissionFactorGroup
                    && (string) $merchandisingItemEmissionFactor->emissionFactorGroupId() !== $filter->emissionFactorGroup
                ) {
                    continue;
                }

                yield $this->baseRow($merchandisingItem) + $this->factorRow($merchandisingItemEmissionFactor);
            }
        }
    }

    public function isAuthorized(User $user): bool
    {
        return $this->authorizer->canDoThat(
            new MerchandisingItem(id: MerchandisingItemId::generate()),
            Action::Export,
            $user,
        );
    }

    /** @return array<string, scalar|null> */
    private function baseRow(MerchandisingItem $merchandisingItem): array
    {
        return [
            'merchandisingItem' => (string) $merchandisingItem->id(),
            'name' => $merchandisingItem->name(),
            'category' => $merchandisingItem->category()->value,
            'unit' => $merchandisingItem->unit()->value,
        ];
    }

    /** @return array<string, scalar|null> */
    private function factorRow(MerchandisingItemEmissionFactor $merchandisingItemEmissionFactor): array
    {
        return [
            'emissionFactorGroup' => $this->groupName($merchandisingItemEmissionFactor->emissionFactorGroupId()),
            'year' => $merchandisingItemEmissionFactor->newFactor()->year(),
            'co2eq' => $merchandisingItemEmissionFactor->newFactor()->co2eq(),
            'co2' => $merchandisingItemEmissionFactor->newFactor()->co2(),
            'ch4' => $merchandisingItemEmissionFactor->newFactor()->ch4(),
            'n2o' => $merchandisingItemEmissionFactor->newFactor()->n2o(),
            'recycledCo2eq' => $merchandisingItemEmissionFactor->recycledFactor()->co2eq(),
            'recycledCo2' => $merchandisingItemEmissionFactor->recycledFactor()->co2(),
            'recycledCh4' => $merchandisingItemEmissionFactor->recycledFactor()->ch4(),
            'recycledN2o' => $merchandisingItemEmissionFactor->recycledFactor()->n2o(),
            'source' => $merchandisingItemEmissionFactor->newFactor()->source() ?? '',
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
