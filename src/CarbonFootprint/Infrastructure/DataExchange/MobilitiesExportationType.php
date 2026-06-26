<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\Mobility\Export\MobilitiesExportFilter;
use App\CarbonFootprint\Application\Mobility\MobilityAuthorizer;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityEmissionFactor;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\AdminMobilitiesExportType;
use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Translation\TranslatableMessage;

class MobilitiesExportationType implements ExportationType
{
    public function __construct(
        private readonly MobilityRepository $repository,
        private readonly MobilityAuthorizer $authorizer,
    ) {}

    public function code(): string
    {
        return 'mobilities';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Movilidad');
    }

    public function returnRoute(): string
    {
        return 'admin_mobilities';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('mobility', new TranslatableMessage('Identificador'), selectedByDefault: false),
            new ExportColumnDefinition('name', new TranslatableMessage('Nombre')),
            new ExportColumnDefinition('unit', new TranslatableMessage('Unidad')),
            new ExportColumnDefinition('year', new TranslatableMessage('Año')),
            new ExportColumnDefinition('co2eq', new TranslatableMessage('CO2eq')),
            new ExportColumnDefinition('co2', new TranslatableMessage('CO2')),
            new ExportColumnDefinition('ch4', new TranslatableMessage('CH4')),
            new ExportColumnDefinition('n2o', new TranslatableMessage('N2O')),
            new ExportColumnDefinition('source', new TranslatableMessage('Fuente')),
        ];
    }

    public function filterFormClass(): string
    {
        return AdminMobilitiesExportType::class;
    }

    public function createFilter(): object
    {
        return new MobilitiesExportFilter();
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof MobilitiesExportFilter) {
            return [];
        }

        return [
            'name' => $filter->name,
            'orderBy' => $filter->orderBy,
            'orderWay' => $filter->orderWay,
        ];
    }

    public function hydrateFilter(array $criteria): object
    {
        $filter = new MobilitiesExportFilter();

        $filter->name = isset($criteria['name']) ? (string) $criteria['name'] : null;
        $filter->orderBy = isset($criteria['orderBy']) ? (string) $criteria['orderBy'] : 'default';
        $filter->orderWay = isset($criteria['orderWay']) ? (string) $criteria['orderWay'] : 'ASC';
        $filter->limit = 0;

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof MobilitiesExportFilter) {
            return;
        }

        $filter->limit = 0;

        foreach ($this->repository->findByFilter($filter) as $mobility) {
            $emissionFactors = $mobility->emissionFactors();

            if (0 === $emissionFactors->count()) {
                yield $this->baseRow($mobility) + $this->emptyFactorRow();

                continue;
            }

            foreach ($emissionFactors as $mobilityEmissionFactor) {
                yield $this->baseRow($mobility) + $this->factorRow($mobilityEmissionFactor);
            }
        }
    }

    public function isAuthorized(User $user): bool
    {
        return $this->authorizer->canDoThat(
            new Mobility(id: MobilityId::generate()),
            Action::Export,
            $user,
        );
    }

    /** @return array<string, scalar|null> */
    private function baseRow(Mobility $mobility): array
    {
        return [
            'mobility' => (string) $mobility->id(),
            'name' => $mobility->name(),
            'unit' => $mobility->unit()->value,
        ];
    }

    /** @return array<string, scalar|null> */
    private function factorRow(MobilityEmissionFactor $mobilityEmissionFactor): array
    {
        $emissionFactor = $mobilityEmissionFactor->emissionFactor();

        return [
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
            'year' => '',
            'co2eq' => '',
            'co2' => '',
            'ch4' => '',
            'n2o' => '',
            'source' => '',
        ];
    }
}
