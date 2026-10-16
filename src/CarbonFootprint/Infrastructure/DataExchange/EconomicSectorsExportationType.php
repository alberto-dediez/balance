<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\EconomicSector\EconomicSectorAuthorizer;
use App\CarbonFootprint\Application\EconomicSector\Export\EconomicSectorsExportFilter;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSector;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorEmissionFactor;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\AdminEconomicSectorsExportType;
use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Translation\TranslatableMessage;

class EconomicSectorsExportationType implements ExportationType
{
    public function __construct(
        private readonly EconomicSectorRepository $repository,
        private readonly EconomicSectorAuthorizer $authorizer,
        private readonly EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function code(): string
    {
        return 'economic_sectors';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Sectores económicos');
    }

    public function returnRoute(): string
    {
        return 'admin_economic_sectors';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('economicSector', new TranslatableMessage('Identificador'), selectedByDefault: false),
            new ExportColumnDefinition('classificationCode', new TranslatableMessage('Código CNAE/NACE')),
            new ExportColumnDefinition('name', new TranslatableMessage('Nombre')),
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
        return AdminEconomicSectorsExportType::class;
    }

    public function createFilter(): object
    {
        return new EconomicSectorsExportFilter();
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof EconomicSectorsExportFilter) {
            return [];
        }

        return [
            'name' => $filter->name,
            'classificationCode' => $filter->classificationCode,
            'emissionFactorGroup' => $filter->emissionFactorGroup,
            'orderBy' => $filter->orderBy,
            'orderWay' => $filter->orderWay,
        ];
    }

    public function hydrateFilter(array $criteria): object
    {
        $filter = new EconomicSectorsExportFilter();

        $filter->name = isset($criteria['name']) ? (string) $criteria['name'] : null;
        $filter->classificationCode = isset($criteria['classificationCode']) ? (string) $criteria['classificationCode'] : null;
        $filter->emissionFactorGroup = isset($criteria['emissionFactorGroup']) ? (string) $criteria['emissionFactorGroup'] : null;
        $filter->orderBy = isset($criteria['orderBy']) ? (string) $criteria['orderBy'] : 'default';
        $filter->orderWay = isset($criteria['orderWay']) ? (string) $criteria['orderWay'] : 'ASC';
        $filter->limit = 0;

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof EconomicSectorsExportFilter) {
            return;
        }

        $filter->limit = 0;
        $this->groupNameCache = [];

        foreach ($this->repository->findByFilter($filter) as $economicSector) {
            $emissionFactors = $economicSector->emissionFactors();

            if (0 === $emissionFactors->count()) {
                yield $this->baseRow($economicSector) + $this->emptyFactorRow();

                continue;
            }

            foreach ($emissionFactors as $economicSectorEmissionFactor) {
                if (null !== $filter->emissionFactorGroup
                    && (string) $economicSectorEmissionFactor->emissionFactorGroupId() !== $filter->emissionFactorGroup
                ) {
                    continue;
                }

                yield $this->baseRow($economicSector) + $this->factorRow($economicSectorEmissionFactor);
            }
        }
    }

    public function isAuthorized(User $user): bool
    {
        return $this->authorizer->canDoThat(
            new EconomicSector(id: EconomicSectorId::generate()),
            Action::Export,
            $user,
        );
    }

    /** @return array<string, scalar|null> */
    private function baseRow(EconomicSector $economicSector): array
    {
        return [
            'economicSector' => (string) $economicSector->id(),
            'classificationCode' => $economicSector->classificationCode() ?? '',
            'name' => $economicSector->name(),
            'unit' => $economicSector->unit()->value,
        ];
    }

    /** @return array<string, scalar|null> */
    private function factorRow(EconomicSectorEmissionFactor $economicSectorEmissionFactor): array
    {
        return [
            'emissionFactorGroup' => $this->groupName($economicSectorEmissionFactor->emissionFactorGroupId()),
            'year' => $economicSectorEmissionFactor->emissionFactor()->year(),
            'co2eq' => $economicSectorEmissionFactor->emissionFactor()->co2eq(),
            'co2' => $economicSectorEmissionFactor->emissionFactor()->co2(),
            'ch4' => $economicSectorEmissionFactor->emissionFactor()->ch4(),
            'n2o' => $economicSectorEmissionFactor->emissionFactor()->n2o(),
            'source' => $economicSectorEmissionFactor->emissionFactor()->source() ?? '',
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
