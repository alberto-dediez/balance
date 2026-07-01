<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\ElectricityMarketer\ElectricityMarketerAuthorizer;
use App\CarbonFootprint\Application\ElectricityMarketer\Export\ElectricityMarketersExportFilter;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerEmissionFactor;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\AdminElectricityMarketersExportType;
use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Translation\TranslatableMessage;

class ElectricityMarketersExportationType implements ExportationType
{
    public function __construct(
        private readonly ElectricityMarketerRepository $repository,
        private readonly ElectricityMarketerAuthorizer $authorizer,
        private readonly EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function code(): string
    {
        return 'electricity_marketers';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Comercializadoras eléctricas');
    }

    public function returnRoute(): string
    {
        return 'admin_electricity_marketers';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('electricityMarketer', new TranslatableMessage('Identificador'), selectedByDefault: false),
            new ExportColumnDefinition('name', new TranslatableMessage('Nombre')),
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
        return AdminElectricityMarketersExportType::class;
    }

    public function createFilter(): object
    {
        return new ElectricityMarketersExportFilter();
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof ElectricityMarketersExportFilter) {
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
        $filter = new ElectricityMarketersExportFilter();

        $filter->name = isset($criteria['name']) ? (string) $criteria['name'] : null;
        $filter->emissionFactorGroup = isset($criteria['emissionFactorGroup']) ? (string) $criteria['emissionFactorGroup'] : null;
        $filter->orderBy = isset($criteria['orderBy']) ? (string) $criteria['orderBy'] : 'default';
        $filter->orderWay = isset($criteria['orderWay']) ? (string) $criteria['orderWay'] : 'ASC';
        $filter->limit = 0;

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof ElectricityMarketersExportFilter) {
            return;
        }

        $filter->limit = 0;
        $this->groupNameCache = [];

        foreach ($this->repository->findByFilter($filter) as $electricityMarketer) {
            $emissionFactors = $electricityMarketer->emissionFactors();

            if (0 === $emissionFactors->count()) {
                yield $this->baseRow($electricityMarketer) + $this->emptyFactorRow();

                continue;
            }

            foreach ($emissionFactors as $electricityMarketerEmissionFactor) {
                if (null !== $filter->emissionFactorGroup
                    && (string) $electricityMarketerEmissionFactor->emissionFactorGroupId() !== $filter->emissionFactorGroup
                ) {
                    continue;
                }

                yield $this->baseRow($electricityMarketer) + $this->factorRow($electricityMarketerEmissionFactor);
            }
        }
    }

    public function isAuthorized(User $user): bool
    {
        return $this->authorizer->canDoThat(
            new ElectricityMarketer(id: ElectricityMarketerId::generate()),
            Action::Export,
            $user,
        );
    }

    /** @return array<string, scalar|null> */
    private function baseRow(ElectricityMarketer $electricityMarketer): array
    {
        return [
            'electricityMarketer' => (string) $electricityMarketer->id(),
            'name' => $electricityMarketer->name(),
        ];
    }

    /** @return array<string, scalar|null> */
    private function factorRow(ElectricityMarketerEmissionFactor $electricityMarketerEmissionFactor): array
    {
        $emissionFactor = $electricityMarketerEmissionFactor->emissionFactor();

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
