<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\MachineryFuel\Export\MachineryFuelsExportFilter;
use App\CarbonFootprint\Application\MachineryFuel\MachineryFuelAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuel;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelEmissionFactor;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\AdminMachineryFuelsExportType;
use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Translation\TranslatableMessage;

class MachineryFuelsExportationType implements ExportationType
{
    public function __construct(
        private readonly MachineryFuelRepository $repository,
        private readonly MachineryFuelAuthorizer $authorizer,
        private readonly EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function code(): string
    {
        return 'machinery_fuels';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Combustible de maquinaria');
    }

    public function returnRoute(): string
    {
        return 'admin_machinery_fuels';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('machinery_fuel', new TranslatableMessage('Identificador'), selectedByDefault: false),
            new ExportColumnDefinition('name', new TranslatableMessage('Nombre')),
            new ExportColumnDefinition('unit', new TranslatableMessage('Unidad')),
            new ExportColumnDefinition('machinery_type', new TranslatableMessage('Tipo de maquinaria')),
            new ExportColumnDefinition('year', new TranslatableMessage('Año')),
            new ExportColumnDefinition('co2eq', new TranslatableMessage('CO2eq')),
            new ExportColumnDefinition('co2', new TranslatableMessage('CO2')),
            new ExportColumnDefinition('ch4', new TranslatableMessage('CH4')),
            new ExportColumnDefinition('n2o', new TranslatableMessage('N2O')),
            new ExportColumnDefinition('source', new TranslatableMessage('Fuente')),
            new ExportColumnDefinition('wellToTankCo2eq', new TranslatableMessage('CO2eq WTT')),
            new ExportColumnDefinition('wellToTankCo2', new TranslatableMessage('CO2 WTT')),
            new ExportColumnDefinition('wellToTankCh4', new TranslatableMessage('CH4 WTT')),
            new ExportColumnDefinition('wellToTankN2o', new TranslatableMessage('N2O WTT')),
            new ExportColumnDefinition('wellToTankSource', new TranslatableMessage('Fuente WTT')),
            new ExportColumnDefinition('emissionFactorGroup', new TranslatableMessage('Grupo')),
        ];
    }

    public function filterFormClass(): string
    {
        return AdminMachineryFuelsExportType::class;
    }

    public function createFilter(): object
    {
        return new MachineryFuelsExportFilter();
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof MachineryFuelsExportFilter) {
            return [];
        }

        return [
            'name' => $filter->name,
            'emissionFactorGroup' => $filter->emissionFactorGroup,
            'machineryType' => $filter->machineryType?->value,
            'orderBy' => $filter->orderBy,
            'orderWay' => $filter->orderWay,
        ];
    }

    public function hydrateFilter(array $criteria): object
    {
        $filter = new MachineryFuelsExportFilter();

        $filter->name = isset($criteria['name']) ? (string) $criteria['name'] : null;
        $filter->emissionFactorGroup = isset($criteria['emissionFactorGroup']) ? (string) $criteria['emissionFactorGroup'] : null;
        $filter->machineryType = isset($criteria['machineryType'])
            ? \App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryType::tryFrom((string) $criteria['machineryType'])
            : null;
        $filter->orderBy = isset($criteria['orderBy']) ? (string) $criteria['orderBy'] : 'default';
        $filter->orderWay = isset($criteria['orderWay']) ? (string) $criteria['orderWay'] : 'ASC';
        $filter->limit = 0;

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof MachineryFuelsExportFilter) {
            return;
        }

        $filter->limit = 0;
        $this->groupNameCache = [];

        foreach ($this->repository->findByFilter($filter) as $machineryFuel) {
            $emissionFactors = $machineryFuel->emissionFactors();

            if (0 === $emissionFactors->count()) {
                yield $this->baseRow($machineryFuel) + $this->emptyFactorRow();

                continue;
            }

            foreach ($emissionFactors as $machineryFuelEmissionFactor) {
                if (null !== $filter->emissionFactorGroup
                    && (string) $machineryFuelEmissionFactor->emissionFactorGroupId() !== $filter->emissionFactorGroup
                ) {
                    continue;
                }

                yield $this->baseRow($machineryFuel) + $this->factorRow($machineryFuelEmissionFactor);
            }
        }
    }

    public function isAuthorized(User $user): bool
    {
        return $this->authorizer->canDoThat(
            new MachineryFuel(id: MachineryFuelId::generate()),
            Action::Export,
            $user,
        );
    }

    /** @return array<string, scalar|null> */
    private function baseRow(MachineryFuel $machineryFuel): array
    {
        return [
            'machinery_fuel' => (string) $machineryFuel->id(),
            'name' => $machineryFuel->name(),
            'unit' => $machineryFuel->unit()->value,
            'machinery_type' => $machineryFuel->machineryType()->value,
        ];
    }

    /** @return array<string, scalar|null> */
    private function factorRow(MachineryFuelEmissionFactor $machineryFuelEmissionFactor): array
    {
        $emissionFactor = $machineryFuelEmissionFactor->emissionFactor();
        $wellToTankFactor = $machineryFuelEmissionFactor->wellToTankFactor();

        return [
            'emissionFactorGroup' => $this->groupName($emissionFactor->emissionFactorGroupId()),
            'year' => $emissionFactor->year(),
            'co2eq' => $emissionFactor->co2eq(),
            'co2' => $emissionFactor->co2(),
            'ch4' => $emissionFactor->ch4(),
            'n2o' => $emissionFactor->n2o(),
            'source' => $emissionFactor->source() ?? '',
            'wellToTankCo2eq' => $wellToTankFactor->co2eq(),
            'wellToTankCo2' => $wellToTankFactor->co2(),
            'wellToTankCh4' => $wellToTankFactor->ch4(),
            'wellToTankN2o' => $wellToTankFactor->n2o(),
            'wellToTankSource' => $wellToTankFactor->source() ?? '',
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
            'wellToTankCo2eq' => '',
            'wellToTankCo2' => '',
            'wellToTankCh4' => '',
            'wellToTankN2o' => '',
            'wellToTankSource' => '',
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
