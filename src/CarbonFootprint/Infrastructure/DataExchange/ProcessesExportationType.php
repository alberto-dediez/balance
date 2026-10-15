<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\Process\Export\ProcessesExportFilter;
use App\CarbonFootprint\Application\Process\ProcessAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Process\Process;
use App\CarbonFootprint\Domain\Model\Process\ProcessEmissionFactor;
use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\AdminProcessesExportType;
use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Translation\TranslatableMessage;

class ProcessesExportationType implements ExportationType
{
    public function __construct(
        private readonly ProcessRepository $repository,
        private readonly ProcessAuthorizer $authorizer,
        private readonly EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function code(): string
    {
        return 'processes';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Procesos');
    }

    public function returnRoute(): string
    {
        return 'admin_processes';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('process', new TranslatableMessage('Identificador'), selectedByDefault: false),
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
        return AdminProcessesExportType::class;
    }

    public function createFilter(): object
    {
        return new ProcessesExportFilter();
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof ProcessesExportFilter) {
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
        $filter = new ProcessesExportFilter();

        $filter->name = isset($criteria['name']) ? (string) $criteria['name'] : null;
        $filter->emissionFactorGroup = isset($criteria['emissionFactorGroup']) ? (string) $criteria['emissionFactorGroup'] : null;
        $filter->orderBy = isset($criteria['orderBy']) ? (string) $criteria['orderBy'] : 'default';
        $filter->orderWay = isset($criteria['orderWay']) ? (string) $criteria['orderWay'] : 'ASC';
        $filter->limit = 0;

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof ProcessesExportFilter) {
            return;
        }

        $filter->limit = 0;
        $this->groupNameCache = [];

        foreach ($this->repository->findByFilter($filter) as $process) {
            $emissionFactors = $process->emissionFactors();

            if (0 === $emissionFactors->count()) {
                yield $this->baseRow($process) + $this->emptyFactorRow();

                continue;
            }

            foreach ($emissionFactors as $processEmissionFactor) {
                if (null !== $filter->emissionFactorGroup
                    && (string) $processEmissionFactor->emissionFactorGroupId() !== $filter->emissionFactorGroup
                ) {
                    continue;
                }

                yield $this->baseRow($process) + $this->factorRow($processEmissionFactor);
            }
        }
    }

    public function isAuthorized(User $user): bool
    {
        return $this->authorizer->canDoThat(
            new Process(id: ProcessId::generate()),
            Action::Export,
            $user,
        );
    }

    /** @return array<string, scalar|null> */
    private function baseRow(Process $process): array
    {
        return [
            'process' => (string) $process->id(),
            'name' => $process->name(),
            'unit' => $process->unit()->value,
        ];
    }

    /** @return array<string, scalar|null> */
    private function factorRow(ProcessEmissionFactor $processEmissionFactor): array
    {
        return [
            'emissionFactorGroup' => $this->groupName($processEmissionFactor->emissionFactorGroupId()),
            'year' => $processEmissionFactor->emissionFactor()->year(),
            'co2eq' => $processEmissionFactor->emissionFactor()->co2eq(),
            'co2' => $processEmissionFactor->emissionFactor()->co2(),
            'ch4' => $processEmissionFactor->emissionFactor()->ch4(),
            'n2o' => $processEmissionFactor->emissionFactor()->n2o(),
            'source' => $processEmissionFactor->emissionFactor()->source() ?? '',
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
