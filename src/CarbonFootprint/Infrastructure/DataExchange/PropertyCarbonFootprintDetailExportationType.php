<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\CarbonFootprint\Export\CarbonFootprintDetailRowsBuilder;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Export\PropertyCarbonFootprintDetailExportFilter;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\PropertyCarbonFootprintDetailExportType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\DataExchange\Domain\Model\Exportation\FilterAuthorizedExportationType;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentRoles\CurrentRolesProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatableMessage;

class PropertyCarbonFootprintDetailExportationType implements FilterAuthorizedExportationType
{
    private const array EXCLUDED_COLUMNS = ['property', 'manualCo2eq'];

    public function __construct(
        private readonly PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private readonly PropertyCarbonFootprintAuthorizer $authorizer,
        private readonly CarbonFootprintDetailRowsBuilder $rowsBuilder,
        private readonly CarbonFootprintDetailCellsFormatter $cellsFormatter,
        private readonly CurrentRolesProvider $currentRolesProvider,
        private readonly RequestStack $requestStack,
    ) {}

    public function code(): string
    {
        return 'property_carbon_footprint_detail';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Detalle de huella de propiedad');
    }

    public function returnRoute(): string
    {
        return 'property_carbon_footprints';
    }

    public function columnDefinitions(): array
    {
        return $this->cellsFormatter->commonColumns(new ExportColumnDefinition('stage', new TranslatableMessage('Alcance')), self::EXCLUDED_COLUMNS);
    }

    public function filterFormClass(): string
    {
        return PropertyCarbonFootprintDetailExportType::class;
    }

    public function createFilter(): object
    {
        $query = $this->requestStack->getCurrentRequest()?->query;

        if (null === $query) {
            return new PropertyCarbonFootprintDetailExportFilter();
        }

        $propertyCarbonFootprintId = $this->string($query->get('propertyCarbonFootprintId'));

        return new PropertyCarbonFootprintDetailExportFilter(
            propertyCarbonFootprintId: null === $propertyCarbonFootprintId ? null : new PropertyCarbonFootprintId($propertyCarbonFootprintId),
        );
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof PropertyCarbonFootprintDetailExportFilter) {
            return [];
        }

        return [
            'propertyCarbonFootprintId' => $filter->propertyCarbonFootprintId instanceof PropertyCarbonFootprintId ? (string) $filter->propertyCarbonFootprintId : null,
        ];
    }

    public function hydrateFilter(array $criteria): object
    {
        $propertyCarbonFootprintId = $this->string($criteria['propertyCarbonFootprintId'] ?? null);

        return new PropertyCarbonFootprintDetailExportFilter(
            propertyCarbonFootprintId: null === $propertyCarbonFootprintId ? null : new PropertyCarbonFootprintId($propertyCarbonFootprintId),
        );
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        $propertyCarbonFootprint = $this->propertyCarbonFootprint($filter);

        if (null === $propertyCarbonFootprint) {
            return;
        }

        foreach ($this->rowsBuilder->build($propertyCarbonFootprint) as $row) {
            yield $this->cellsFormatter->cells($row, 'stage', 'scope', self::EXCLUDED_COLUMNS);
        }
    }

    public function isAuthorized(User $user): bool
    {
        $currentRoles = $this->currentRolesProvider->currentRoles($user);

        return null === $currentRoles ? $user->hasRole(Role::Owner) : in_array(Role::Owner, $currentRoles, true);
    }

    public function isAuthorizedForFilter(object $filter, User $user): bool
    {
        $propertyCarbonFootprint = $this->propertyCarbonFootprint($filter);

        if (null === $propertyCarbonFootprint || !$propertyCarbonFootprint->isCompleted()) {
            return false;
        }

        return $this->authorizer->canDoThat($propertyCarbonFootprint, Action::Export, $user);
    }

    private function propertyCarbonFootprint(object $filter): ?PropertyCarbonFootprint
    {
        if (!$filter instanceof PropertyCarbonFootprintDetailExportFilter || !$filter->propertyCarbonFootprintId instanceof PropertyCarbonFootprintId) {
            return null;
        }

        return $this->propertyCarbonFootprintRepository->find($filter->propertyCarbonFootprintId);
    }

    private function string(mixed $value): ?string
    {
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        return trim($value);
    }
}
