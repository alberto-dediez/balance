<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\GdOFactor\Export\GdOFactorsExportFilter;
use App\CarbonFootprint\Application\GdOFactor\GdOFactorAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactor;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorEmissionFactor;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\AdminGdOFactorsExportType;
use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Translation\TranslatableMessage;

class GdOFactorsExportationType implements ExportationType
{
    public function __construct(
        private readonly GdOFactorRepository $repository,
        private readonly GdOFactorAuthorizer $authorizer,
        private readonly EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function code(): string
    {
        return 'gdo_factors';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Factores GdO');
    }

    public function returnRoute(): string
    {
        return 'admin_gdo_factors';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('gdOFactor', new TranslatableMessage('Identificador'), selectedByDefault: false),
            new ExportColumnDefinition('name', new TranslatableMessage('Nombre')),
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
        return AdminGdOFactorsExportType::class;
    }

    public function createFilter(): object
    {
        return new GdOFactorsExportFilter();
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof GdOFactorsExportFilter) {
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
        $filter = new GdOFactorsExportFilter();

        $filter->name = isset($criteria['name']) ? (string) $criteria['name'] : null;
        $filter->emissionFactorGroup = isset($criteria['emissionFactorGroup']) ? (string) $criteria['emissionFactorGroup'] : null;
        $filter->orderBy = isset($criteria['orderBy']) ? (string) $criteria['orderBy'] : 'default';
        $filter->orderWay = isset($criteria['orderWay']) ? (string) $criteria['orderWay'] : 'ASC';
        $filter->limit = 0;

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof GdOFactorsExportFilter) {
            return;
        }

        $filter->limit = 0;
        $this->groupNameCache = [];

        foreach ($this->repository->findByFilter($filter) as $gdOFactor) {
            $emissionFactors = $gdOFactor->emissionFactors();

            if (0 === $emissionFactors->count()) {
                yield $this->baseRow($gdOFactor) + $this->emptyFactorRow();

                continue;
            }

            foreach ($emissionFactors as $gdOFactorEmissionFactor) {
                if (null !== $filter->emissionFactorGroup
                    && (string) $gdOFactorEmissionFactor->emissionFactorGroupId() !== $filter->emissionFactorGroup
                ) {
                    continue;
                }

                yield $this->baseRow($gdOFactor) + $this->factorRow($gdOFactorEmissionFactor);
            }
        }
    }

    public function isAuthorized(User $user): bool
    {
        return $this->authorizer->canDoThat(
            new GdOFactor(id: GdOFactorId::generate()),
            Action::Export,
            $user,
        );
    }

    /** @return array<string, scalar|null> */
    private function baseRow(GdOFactor $gdOFactor): array
    {
        return [
            'gdOFactor' => (string) $gdOFactor->id(),
            'name' => $gdOFactor->name(),
        ];
    }

    /** @return array<string, scalar|null> */
    private function factorRow(GdOFactorEmissionFactor $gdOFactorEmissionFactor): array
    {
        $emissionFactor = $gdOFactorEmissionFactor->emissionFactor();
        $wellToTankFactor = $gdOFactorEmissionFactor->wellToTankFactor();

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
