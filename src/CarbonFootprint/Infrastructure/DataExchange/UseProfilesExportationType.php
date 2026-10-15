<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\UseProfile\Export\UseProfilesExportFilter;
use App\CarbonFootprint\Application\UseProfile\UseProfileAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfile;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileEmissionFactor;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\AdminUseProfilesExportType;
use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Translation\TranslatableMessage;

class UseProfilesExportationType implements ExportationType
{
    public function __construct(
        private readonly UseProfileRepository $repository,
        private readonly UseProfileAuthorizer $authorizer,
        private readonly EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function code(): string
    {
        return 'use_profiles';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Perfiles de uso');
    }

    public function returnRoute(): string
    {
        return 'admin_use_profiles';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('useProfile', new TranslatableMessage('Identificador'), selectedByDefault: false),
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
        return AdminUseProfilesExportType::class;
    }

    public function createFilter(): object
    {
        return new UseProfilesExportFilter();
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof UseProfilesExportFilter) {
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
        $filter = new UseProfilesExportFilter();

        $filter->name = isset($criteria['name']) ? (string) $criteria['name'] : null;
        $filter->emissionFactorGroup = isset($criteria['emissionFactorGroup']) ? (string) $criteria['emissionFactorGroup'] : null;
        $filter->orderBy = isset($criteria['orderBy']) ? (string) $criteria['orderBy'] : 'default';
        $filter->orderWay = isset($criteria['orderWay']) ? (string) $criteria['orderWay'] : 'ASC';
        $filter->limit = 0;

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof UseProfilesExportFilter) {
            return;
        }

        $filter->limit = 0;
        $this->groupNameCache = [];

        foreach ($this->repository->findByFilter($filter) as $useProfile) {
            $emissionFactors = $useProfile->emissionFactors();

            if (0 === $emissionFactors->count()) {
                yield $this->baseRow($useProfile) + $this->emptyFactorRow();

                continue;
            }

            foreach ($emissionFactors as $useProfileEmissionFactor) {
                if (null !== $filter->emissionFactorGroup
                    && (string) $useProfileEmissionFactor->emissionFactorGroupId() !== $filter->emissionFactorGroup
                ) {
                    continue;
                }

                yield $this->baseRow($useProfile) + $this->factorRow($useProfileEmissionFactor);
            }
        }
    }

    public function isAuthorized(User $user): bool
    {
        return $this->authorizer->canDoThat(
            new UseProfile(id: UseProfileId::generate()),
            Action::Export,
            $user,
        );
    }

    /** @return array<string, scalar|null> */
    private function baseRow(UseProfile $useProfile): array
    {
        return [
            'useProfile' => (string) $useProfile->id(),
            'name' => $useProfile->name(),
            'unit' => $useProfile->unit()->value,
        ];
    }

    /** @return array<string, scalar|null> */
    private function factorRow(UseProfileEmissionFactor $useProfileEmissionFactor): array
    {
        return [
            'emissionFactorGroup' => $this->groupName($useProfileEmissionFactor->emissionFactorGroupId()),
            'year' => $useProfileEmissionFactor->emissionFactor()->year(),
            'co2eq' => $useProfileEmissionFactor->emissionFactor()->co2eq(),
            'co2' => $useProfileEmissionFactor->emissionFactor()->co2(),
            'ch4' => $useProfileEmissionFactor->emissionFactor()->ch4(),
            'n2o' => $useProfileEmissionFactor->emissionFactor()->n2o(),
            'source' => $useProfileEmissionFactor->emissionFactor()->source() ?? '',
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
