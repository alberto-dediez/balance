<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\PurchaseService\Export\PurchaseServicesExportFilter;
use App\CarbonFootprint\Application\PurchaseService\PurchaseServiceAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseService;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceCategory;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceEmissionFactor;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\AdminPurchaseServicesExportType;
use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Translation\TranslatableMessage;

class PurchaseServicesExportationType implements ExportationType
{
    public function __construct(
        private readonly PurchaseServiceRepository $repository,
        private readonly PurchaseServiceAuthorizer $authorizer,
        private readonly EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function code(): string
    {
        return 'purchase_services';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Bienes y servicios comprados');
    }

    public function returnRoute(): string
    {
        return 'admin_purchase_services';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('purchaseService', new TranslatableMessage('Identificador'), selectedByDefault: false),
            new ExportColumnDefinition('name', new TranslatableMessage('Nombre')),
            new ExportColumnDefinition('category', new TranslatableMessage('Categoría')),
            new ExportColumnDefinition('unit', new TranslatableMessage('Unidad')),
            new ExportColumnDefinition('year', new TranslatableMessage('Año')),
            new ExportColumnDefinition('co2eq', new TranslatableMessage('CO2eq')),
            new ExportColumnDefinition('co2', new TranslatableMessage('CO2')),
            new ExportColumnDefinition('ch4', new TranslatableMessage('CH4')),
            new ExportColumnDefinition('n2o', new TranslatableMessage('N2O')),
            new ExportColumnDefinition('source', new TranslatableMessage('Fuente')),
            new ExportColumnDefinition('emissionFactorGroup', new TranslatableMessage('Grupo')),
        ];
    }

    public function filterFormClass(): string
    {
        return AdminPurchaseServicesExportType::class;
    }

    public function createFilter(): object
    {
        return new PurchaseServicesExportFilter();
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof PurchaseServicesExportFilter) {
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
        $filter = new PurchaseServicesExportFilter();

        $filter->name = isset($criteria['name']) ? (string) $criteria['name'] : null;
        $filter->emissionFactorGroup = isset($criteria['emissionFactorGroup']) ? (string) $criteria['emissionFactorGroup'] : null;
        $filter->category = isset($criteria['category'])
            ? PurchaseServiceCategory::from((string) $criteria['category'])
            : null;
        $filter->orderBy = isset($criteria['orderBy']) ? (string) $criteria['orderBy'] : 'default';
        $filter->orderWay = isset($criteria['orderWay']) ? (string) $criteria['orderWay'] : 'ASC';
        $filter->limit = 0;

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof PurchaseServicesExportFilter) {
            return;
        }

        $filter->limit = 0;
        $this->groupNameCache = [];

        foreach ($this->repository->findByFilter($filter) as $purchaseService) {
            $emissionFactors = $purchaseService->emissionFactors();

            if (0 === $emissionFactors->count()) {
                yield $this->baseRow($purchaseService) + $this->emptyFactorRow();

                continue;
            }

            foreach ($emissionFactors as $purchaseServiceEmissionFactor) {
                if (null !== $filter->emissionFactorGroup
                    && (string) $purchaseServiceEmissionFactor->emissionFactorGroupId() !== $filter->emissionFactorGroup
                ) {
                    continue;
                }

                yield $this->baseRow($purchaseService) + $this->factorRow($purchaseServiceEmissionFactor);
            }
        }
    }

    public function isAuthorized(User $user): bool
    {
        return $this->authorizer->canDoThat(
            new PurchaseService(id: PurchaseServiceId::generate()),
            Action::Export,
            $user,
        );
    }

    /** @return array<string, scalar|null> */
    private function baseRow(PurchaseService $purchaseService): array
    {
        return [
            'purchaseService' => (string) $purchaseService->id(),
            'name' => $purchaseService->name(),
            'category' => $purchaseService->category()->value,
            'unit' => $purchaseService->unit()->value,
        ];
    }

    /** @return array<string, scalar|null> */
    private function factorRow(PurchaseServiceEmissionFactor $purchaseServiceEmissionFactor): array
    {
        return [
            'emissionFactorGroup' => $this->groupName($purchaseServiceEmissionFactor->emissionFactorGroupId()),
            'year' => $purchaseServiceEmissionFactor->emissionFactor()->year(),
            'co2eq' => $purchaseServiceEmissionFactor->emissionFactor()->co2eq(),
            'co2' => $purchaseServiceEmissionFactor->emissionFactor()->co2(),
            'ch4' => $purchaseServiceEmissionFactor->emissionFactor()->ch4(),
            'n2o' => $purchaseServiceEmissionFactor->emissionFactor()->n2o(),
            'source' => $purchaseServiceEmissionFactor->emissionFactor()->source() ?? '',
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
