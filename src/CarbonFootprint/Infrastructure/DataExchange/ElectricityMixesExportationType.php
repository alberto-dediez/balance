<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\ElectricityMix\ElectricityMixAuthorizer;
use App\CarbonFootprint\Application\ElectricityMix\Export\ElectricityMixesExportFilter;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMix;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixEmissionFactor;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\AdminElectricityMixesExportType;
use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Translation\TranslatableMessage;

class ElectricityMixesExportationType implements ExportationType
{
    public function __construct(
        private readonly ElectricityMixRepository $repository,
        private readonly ElectricityMixAuthorizer $authorizer,
        private readonly EmissionFactorGroupRepository $emissionFactorGroupRepository,
        private readonly CountryRepository $countryRepository,
    ) {}

    public function code(): string
    {
        return 'electricity_mixes';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Mixes eléctricos');
    }

    public function returnRoute(): string
    {
        return 'admin_electricity_mixes';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('electricityMix', new TranslatableMessage('Identificador'), selectedByDefault: false),
            new ExportColumnDefinition('country', new TranslatableMessage('País')),
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
        return AdminElectricityMixesExportType::class;
    }

    public function createFilter(): object
    {
        return new ElectricityMixesExportFilter();
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof ElectricityMixesExportFilter) {
            return [];
        }

        return [
            'countryId' => null !== $filter->countryId ? (string) $filter->countryId : null,
            'emissionFactorGroup' => $filter->emissionFactorGroup,
            'orderBy' => $filter->orderBy,
            'orderWay' => $filter->orderWay,
        ];
    }

    public function hydrateFilter(array $criteria): object
    {
        $filter = new ElectricityMixesExportFilter();

        $filter->countryId = isset($criteria['countryId']) ? new CountryId((string) $criteria['countryId']) : null;
        $filter->emissionFactorGroup = isset($criteria['emissionFactorGroup']) ? (string) $criteria['emissionFactorGroup'] : null;
        $filter->orderBy = isset($criteria['orderBy']) ? (string) $criteria['orderBy'] : 'default';
        $filter->orderWay = isset($criteria['orderWay']) ? (string) $criteria['orderWay'] : 'ASC';
        $filter->limit = 0;

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof ElectricityMixesExportFilter) {
            return;
        }

        $filter->limit = 0;
        $this->groupNameCache = [];
        $this->countryNameCache = [];

        foreach ($this->repository->findByFilter($filter) as $electricityMix) {
            $emissionFactors = $electricityMix->emissionFactors();

            if (0 === $emissionFactors->count()) {
                yield $this->baseRow($electricityMix) + $this->emptyFactorRow();

                continue;
            }

            foreach ($emissionFactors as $electricityMixEmissionFactor) {
                if (null !== $filter->emissionFactorGroup
                    && (string) $electricityMixEmissionFactor->emissionFactorGroupId() !== $filter->emissionFactorGroup
                ) {
                    continue;
                }

                yield $this->baseRow($electricityMix) + $this->factorRow($electricityMixEmissionFactor);
            }
        }
    }

    public function isAuthorized(User $user): bool
    {
        return $this->authorizer->canDoThat(
            new ElectricityMix(id: ElectricityMixId::generate()),
            Action::Export,
            $user,
        );
    }

    /** @return array<string, scalar|null> */
    private function baseRow(ElectricityMix $electricityMix): array
    {
        return [
            'electricityMix' => (string) $electricityMix->id(),
            'country' => $this->countryName($electricityMix->countryId()),
        ];
    }

    /** @return array<string, scalar|null> */
    private function factorRow(ElectricityMixEmissionFactor $electricityMixEmissionFactor): array
    {
        $emissionFactor = $electricityMixEmissionFactor->emissionFactor();
        $wellToTankFactor = $electricityMixEmissionFactor->wellToTankFactor();

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
    private array $countryNameCache = [];

    private function countryName(CountryId $countryId): string
    {
        $key = (string) $countryId;

        if (!isset($this->countryNameCache[$key])) {
            $country = $this->countryRepository->find($countryId);
            $this->countryNameCache[$key] = null !== $country ? $country->name() : '';
        }

        return $this->countryNameCache[$key];
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
