<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\Waste\Export\WastesExportFilter;
use App\CarbonFootprint\Application\Waste\WasteAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Waste\Waste;
use App\CarbonFootprint\Domain\Model\Waste\WasteEmissionFactor;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\AdminWastesExportType;
use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Translation\TranslatableMessage;

class WastesExportationType implements ExportationType
{
    public function __construct(
        private readonly WasteRepository $repository,
        private readonly WasteAuthorizer $authorizer,
        private readonly EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function code(): string
    {
        return 'wastes';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Residuos');
    }

    public function returnRoute(): string
    {
        return 'admin_wastes';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('waste', new TranslatableMessage('Identificador'), selectedByDefault: false),
            new ExportColumnDefinition('name', new TranslatableMessage('Nombre')),
            new ExportColumnDefinition('unit', new TranslatableMessage('Unidad')),
            new ExportColumnDefinition('density', new TranslatableMessage('Densidad')),
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
        return AdminWastesExportType::class;
    }

    public function createFilter(): object
    {
        return new WastesExportFilter();
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof WastesExportFilter) {
            return [];
        }

        return [
            'name' => $filter->name,
            'emissionFactorGroup' => $filter->emissionFactorGroup,
            'orderBy' => $filter->orderBy,
            'orderWay' => $filter->orderWay,
        ];
    }

    public function hydrateFilter(array $criteria): object
    {
        $filter = new WastesExportFilter();

        $filter->name = isset($criteria['name']) ? (string) $criteria['name'] : null;
        $filter->emissionFactorGroup = isset($criteria['emissionFactorGroup']) ? (string) $criteria['emissionFactorGroup'] : null;
        $filter->orderBy = isset($criteria['orderBy']) ? (string) $criteria['orderBy'] : 'default';
        $filter->orderWay = isset($criteria['orderWay']) ? (string) $criteria['orderWay'] : 'ASC';
        $filter->limit = 0;

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof WastesExportFilter) {
            return;
        }

        $filter->limit = 0;
        $this->groupNameCache = [];

        foreach ($this->repository->findByFilter($filter) as $waste) {
            $emissionFactors = $waste->emissionFactors();

            if (0 === $emissionFactors->count()) {
                yield $this->baseRow($waste) + $this->emptyFactorRow();

                continue;
            }

            foreach ($emissionFactors as $wasteEmissionFactor) {
                if (null !== $filter->emissionFactorGroup
                    && (string) $wasteEmissionFactor->emissionFactorGroupId() !== $filter->emissionFactorGroup
                ) {
                    continue;
                }

                yield $this->baseRow($waste) + $this->factorRow($wasteEmissionFactor);
            }
        }
    }

    public function isAuthorized(User $user): bool
    {
        return $this->authorizer->canDoThat(
            new Waste(id: WasteId::generate()),
            Action::Export,
            $user,
        );
    }

    /** @return array<string, scalar|null> */
    private function baseRow(Waste $waste): array
    {
        return [
            'waste' => (string) $waste->id(),
            'name' => $waste->name(),
            'unit' => $waste->unit()->value,
            'density' => $waste->density(),
        ];
    }

    /** @return array<string, scalar|null> */
    private function factorRow(WasteEmissionFactor $wasteEmissionFactor): array
    {
        $emissionFactor = $wasteEmissionFactor->emissionFactor();

        return [
            'emissionFactorGroup' => $this->groupName($emissionFactor->emissionFactorGroupId()),
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
