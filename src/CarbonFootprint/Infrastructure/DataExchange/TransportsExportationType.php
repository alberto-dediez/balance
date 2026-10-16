<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\Transport\Export\TransportsExportFilter;
use App\CarbonFootprint\Application\Transport\TransportAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Transport\Transport;
use App\CarbonFootprint\Domain\Model\Transport\TransportEmissionFactor;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\AdminTransportsExportType;
use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Translation\TranslatableMessage;

class TransportsExportationType implements ExportationType
{
    public function __construct(
        private readonly TransportRepository $repository,
        private readonly TransportAuthorizer $authorizer,
        private readonly EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function code(): string
    {
        return 'transports';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Medios de transporte');
    }

    public function returnRoute(): string
    {
        return 'admin_transports';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('transport', new TranslatableMessage('Identificador'), selectedByDefault: false),
            new ExportColumnDefinition('name', new TranslatableMessage('Nombre')),
            new ExportColumnDefinition('unit', new TranslatableMessage('Unidad')),
            new ExportColumnDefinition('mode', new TranslatableMessage('Modo')),
            new ExportColumnDefinition('vehicle', new TranslatableMessage('Tipo de vehículo')),
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
        return AdminTransportsExportType::class;
    }

    public function createFilter(): object
    {
        return new TransportsExportFilter();
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof TransportsExportFilter) {
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
        $filter = new TransportsExportFilter();

        $filter->name = isset($criteria['name']) ? (string) $criteria['name'] : null;
        $filter->emissionFactorGroup = isset($criteria['emissionFactorGroup']) ? (string) $criteria['emissionFactorGroup'] : null;
        $filter->orderBy = isset($criteria['orderBy']) ? (string) $criteria['orderBy'] : 'default';
        $filter->orderWay = isset($criteria['orderWay']) ? (string) $criteria['orderWay'] : 'ASC';
        $filter->limit = 0;

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof TransportsExportFilter) {
            return;
        }

        $filter->limit = 0;
        $this->groupNameCache = [];

        foreach ($this->repository->findByFilter($filter) as $transport) {
            $emissionFactors = $transport->emissionFactors();

            if (0 === $emissionFactors->count()) {
                yield $this->baseRow($transport) + $this->emptyFactorRow();

                continue;
            }

            foreach ($emissionFactors as $transportEmissionFactor) {
                if (null !== $filter->emissionFactorGroup
                    && (string) $transportEmissionFactor->emissionFactorGroupId() !== $filter->emissionFactorGroup
                ) {
                    continue;
                }

                yield $this->baseRow($transport) + $this->factorRow($transportEmissionFactor);
            }
        }
    }

    public function isAuthorized(User $user): bool
    {
        return $this->authorizer->canDoThat(
            new Transport(id: TransportId::generate()),
            Action::Export,
            $user,
        );
    }

    /** @return array<string, scalar|null> */
    private function baseRow(Transport $transport): array
    {
        return [
            'transport' => (string) $transport->id(),
            'name' => $transport->name(),
            'unit' => $transport->unit()->value,
            'mode' => $transport->mode()->value,
            'vehicle' => $transport->vehicle()->value,
        ];
    }

    /** @return array<string, scalar|null> */
    private function factorRow(TransportEmissionFactor $transportEmissionFactor): array
    {
        $emissionFactor = $transportEmissionFactor->emissionFactor();

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
