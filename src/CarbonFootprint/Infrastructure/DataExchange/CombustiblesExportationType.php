<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\Combustible\CombustibleAuthorizer;
use App\CarbonFootprint\Application\Combustible\Export\CombustiblesExportFilter;
use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleEmissionFactor;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\AdminCombustiblesExportType;
use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Translation\TranslatableMessage;

class CombustiblesExportationType implements ExportationType
{
    public function __construct(
        private readonly CombustibleRepository $repository,
        private readonly CombustibleAuthorizer $authorizer,
    ) {}

    public function code(): string
    {
        return 'combustibles';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Combustibles');
    }

    public function returnRoute(): string
    {
        return 'admin_combustibles';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('combustible', new TranslatableMessage('Identificador'), selectedByDefault: false),
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
        return AdminCombustiblesExportType::class;
    }

    public function createFilter(): object
    {
        return new CombustiblesExportFilter();
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof CombustiblesExportFilter) {
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
        $filter = new CombustiblesExportFilter();

        $filter->name = isset($criteria['name']) ? (string) $criteria['name'] : null;
        $filter->orderBy = isset($criteria['orderBy']) ? (string) $criteria['orderBy'] : 'default';
        $filter->orderWay = isset($criteria['orderWay']) ? (string) $criteria['orderWay'] : 'ASC';
        $filter->limit = 0;

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof CombustiblesExportFilter) {
            return;
        }

        $filter->limit = 0;

        foreach ($this->repository->findByFilter($filter) as $combustible) {
            $emissionFactors = $combustible->emissionFactors();

            if (0 === $emissionFactors->count()) {
                yield $this->baseRow($combustible) + $this->emptyFactorRow();

                continue;
            }

            foreach ($emissionFactors as $combustibleEmissionFactor) {
                yield $this->baseRow($combustible) + $this->factorRow($combustibleEmissionFactor);
            }
        }
    }

    public function isAuthorized(User $user): bool
    {
        return $this->authorizer->canDoThat(
            new Combustible(id: CombustibleId::generate()),
            Action::Export,
            $user,
        );
    }

    /** @return array<string, scalar|null> */
    private function baseRow(Combustible $combustible): array
    {
        return [
            'combustible' => (string) $combustible->id(),
            'name' => $combustible->name(),
            'unit' => $combustible->unit()->value,
        ];
    }

    /** @return array<string, scalar|null> */
    private function factorRow(CombustibleEmissionFactor $combustibleEmissionFactor): array
    {
        $emissionFactor = $combustibleEmissionFactor->emissionFactor();

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
