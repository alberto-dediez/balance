<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\CapitalGood\CapitalGoodAuthorizer;
use App\CarbonFootprint\Application\CapitalGood\Export\CapitalGoodsExportFilter;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGood;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodCategory;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodEmissionFactor;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\AdminCapitalGoodsExportType;
use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Translation\TranslatableMessage;

class CapitalGoodsExportationType implements ExportationType
{
    public function __construct(
        private readonly CapitalGoodRepository $repository,
        private readonly CapitalGoodAuthorizer $authorizer,
        private readonly EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function code(): string
    {
        return 'capital_goods';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Bienes de capital');
    }

    public function returnRoute(): string
    {
        return 'admin_capital_goods';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('capitalGood', new TranslatableMessage('Identificador'), selectedByDefault: false),
            new ExportColumnDefinition('name', new TranslatableMessage('Nombre')),
            new ExportColumnDefinition('category', new TranslatableMessage('Categoría')),
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
        return AdminCapitalGoodsExportType::class;
    }

    public function createFilter(): object
    {
        return new CapitalGoodsExportFilter();
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof CapitalGoodsExportFilter) {
            return [];
        }

        return [
            'name' => $filter->name,
            'emissionFactorGroup' => $filter->emissionFactorGroup,
            'category' => $filter->category?->value,
            'orderBy' => $filter->orderBy,
            'orderWay' => $filter->orderWay,
        ];
    }

    public function hydrateFilter(array $criteria): object
    {
        $filter = new CapitalGoodsExportFilter();

        $filter->name = isset($criteria['name']) ? (string) $criteria['name'] : null;
        $filter->emissionFactorGroup = isset($criteria['emissionFactorGroup']) ? (string) $criteria['emissionFactorGroup'] : null;
        $filter->category = isset($criteria['category'])
            ? CapitalGoodCategory::from((string) $criteria['category'])
            : null;
        $filter->orderBy = isset($criteria['orderBy']) ? (string) $criteria['orderBy'] : 'default';
        $filter->orderWay = isset($criteria['orderWay']) ? (string) $criteria['orderWay'] : 'ASC';
        $filter->limit = 0;

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof CapitalGoodsExportFilter) {
            return;
        }

        $filter->limit = 0;
        $this->groupNameCache = [];

        foreach ($this->repository->findByFilter($filter) as $capitalGood) {
            $emissionFactors = $capitalGood->emissionFactors();

            if (0 === $emissionFactors->count()) {
                yield $this->baseRow($capitalGood) + $this->emptyFactorRow();

                continue;
            }

            foreach ($emissionFactors as $capitalGoodEmissionFactor) {
                if (null !== $filter->emissionFactorGroup
                    && (string) $capitalGoodEmissionFactor->emissionFactorGroupId() !== $filter->emissionFactorGroup
                ) {
                    continue;
                }

                yield $this->baseRow($capitalGood) + $this->factorRow($capitalGoodEmissionFactor);
            }
        }
    }

    public function isAuthorized(User $user): bool
    {
        return $this->authorizer->canDoThat(
            new CapitalGood(id: CapitalGoodId::generate()),
            Action::Export,
            $user,
        );
    }

    /** @return array<string, scalar|null> */
    private function baseRow(CapitalGood $capitalGood): array
    {
        return [
            'capitalGood' => (string) $capitalGood->id(),
            'name' => $capitalGood->name(),
            'category' => $capitalGood->category()->value,
            'unit' => $capitalGood->unit()->value,
        ];
    }

    /** @return array<string, scalar|null> */
    private function factorRow(CapitalGoodEmissionFactor $capitalGoodEmissionFactor): array
    {
        return [
            'emissionFactorGroup' => $this->groupName($capitalGoodEmissionFactor->emissionFactorGroupId()),
            'year' => $capitalGoodEmissionFactor->emissionFactor()->year(),
            'co2eq' => $capitalGoodEmissionFactor->emissionFactor()->co2eq(),
            'co2' => $capitalGoodEmissionFactor->emissionFactor()->co2(),
            'ch4' => $capitalGoodEmissionFactor->emissionFactor()->ch4(),
            'n2o' => $capitalGoodEmissionFactor->emissionFactor()->n2o(),
            'source' => $capitalGoodEmissionFactor->emissionFactor()->source() ?? '',
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
