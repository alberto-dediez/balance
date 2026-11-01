<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\CarbonFootprint\Export\CarbonFootprintDetailRowsBuilder;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Export\CompanyCarbonFootprintDetailExportFilter;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\CompanyCarbonFootprintDetailExportType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\DataExchange\Domain\Model\Exportation\FilterAuthorizedExportationType;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentRoles\CurrentRolesProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatableMessage;

class CompanyCarbonFootprintDetailExportationType implements FilterAuthorizedExportationType
{
    public function __construct(
        private readonly CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private readonly CompanyCarbonFootprintAuthorizer $authorizer,
        private readonly CarbonFootprintDetailRowsBuilder $rowsBuilder,
        private readonly CarbonFootprintDetailCellsFormatter $cellsFormatter,
        private readonly CurrentRolesProvider $currentRolesProvider,
        private readonly RequestStack $requestStack,
    ) {}

    public function code(): string
    {
        return 'company_carbon_footprint_detail';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Detalle de huella de empresa');
    }

    public function returnRoute(): string
    {
        return 'company_carbon_footprints';
    }

    public function columnDefinitions(): array
    {
        return $this->cellsFormatter->commonColumns(new ExportColumnDefinition('stage', new TranslatableMessage('Alcance')));
    }

    public function filterFormClass(): string
    {
        return CompanyCarbonFootprintDetailExportType::class;
    }

    public function createFilter(): object
    {
        $query = $this->requestStack->getCurrentRequest()?->query;

        if (null === $query) {
            return new CompanyCarbonFootprintDetailExportFilter();
        }

        $companyCarbonFootprintId = $this->string($query->get('companyCarbonFootprintId'));

        return new CompanyCarbonFootprintDetailExportFilter(
            companyCarbonFootprintId: null === $companyCarbonFootprintId ? null : new CompanyCarbonFootprintId($companyCarbonFootprintId),
        );
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof CompanyCarbonFootprintDetailExportFilter) {
            return [];
        }

        return [
            'companyCarbonFootprintId' => $filter->companyCarbonFootprintId instanceof CompanyCarbonFootprintId ? (string) $filter->companyCarbonFootprintId : null,
        ];
    }

    public function hydrateFilter(array $criteria): object
    {
        $companyCarbonFootprintId = $this->string($criteria['companyCarbonFootprintId'] ?? null);

        return new CompanyCarbonFootprintDetailExportFilter(
            companyCarbonFootprintId: null === $companyCarbonFootprintId ? null : new CompanyCarbonFootprintId($companyCarbonFootprintId),
        );
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        $companyCarbonFootprint = $this->companyCarbonFootprint($filter);

        if (null === $companyCarbonFootprint) {
            return;
        }

        foreach ($this->rowsBuilder->build($companyCarbonFootprint) as $row) {
            yield $this->cellsFormatter->cells($row, 'stage', 'scope');
        }
    }

    public function isAuthorized(User $user): bool
    {
        $currentRoles = $this->currentRolesProvider->currentRoles($user);

        return null === $currentRoles ? $user->hasRole(Role::Owner) : in_array(Role::Owner, $currentRoles, true);
    }

    public function isAuthorizedForFilter(object $filter, User $user): bool
    {
        $companyCarbonFootprint = $this->companyCarbonFootprint($filter);

        if (null === $companyCarbonFootprint || !$companyCarbonFootprint->isCompleted()) {
            return false;
        }

        return $this->authorizer->canDoThat($companyCarbonFootprint, Action::Export, $user);
    }

    private function companyCarbonFootprint(object $filter): ?CompanyCarbonFootprint
    {
        if (!$filter instanceof CompanyCarbonFootprintDetailExportFilter || !$filter->companyCarbonFootprintId instanceof CompanyCarbonFootprintId) {
            return null;
        }

        return $this->companyCarbonFootprintRepository->find($filter->companyCarbonFootprintId);
    }

    private function string(mixed $value): ?string
    {
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        return trim($value);
    }
}
