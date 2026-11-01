<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\CarbonFootprint\Export\CarbonFootprintDetailRowsBuilder;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\Export\EventCarbonFootprintDetailExportFilter;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\EventCarbonFootprintDetailExportType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\DataExchange\Domain\Model\Exportation\FilterAuthorizedExportationType;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentRoles\CurrentRolesProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatableMessage;

class EventCarbonFootprintDetailExportationType implements FilterAuthorizedExportationType
{
    private const array EXCLUDED_COLUMNS = ['property', 'acquisitionYear', 'manualCo2eq'];

    public function __construct(
        private readonly EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private readonly EventCarbonFootprintAuthorizer $authorizer,
        private readonly CarbonFootprintDetailRowsBuilder $rowsBuilder,
        private readonly CarbonFootprintDetailCellsFormatter $cellsFormatter,
        private readonly CurrentRolesProvider $currentRolesProvider,
        private readonly RequestStack $requestStack,
    ) {}

    public function code(): string
    {
        return 'event_carbon_footprint_detail';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Detalle de huella de evento');
    }

    public function returnRoute(): string
    {
        return 'events';
    }

    public function columnDefinitions(): array
    {
        return $this->cellsFormatter->commonColumns(new ExportColumnDefinition('phase', new TranslatableMessage('Fase')), self::EXCLUDED_COLUMNS);
    }

    public function filterFormClass(): string
    {
        return EventCarbonFootprintDetailExportType::class;
    }

    public function createFilter(): object
    {
        $query = $this->requestStack->getCurrentRequest()?->query;

        if (null === $query) {
            return new EventCarbonFootprintDetailExportFilter();
        }

        $eventCarbonFootprintId = $this->string($query->get('eventCarbonFootprintId'));

        return new EventCarbonFootprintDetailExportFilter(
            eventCarbonFootprintId: null === $eventCarbonFootprintId ? null : new EventCarbonFootprintId($eventCarbonFootprintId),
        );
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof EventCarbonFootprintDetailExportFilter) {
            return [];
        }

        return [
            'eventCarbonFootprintId' => $filter->eventCarbonFootprintId instanceof EventCarbonFootprintId ? (string) $filter->eventCarbonFootprintId : null,
        ];
    }

    public function hydrateFilter(array $criteria): object
    {
        $eventCarbonFootprintId = $this->string($criteria['eventCarbonFootprintId'] ?? null);

        return new EventCarbonFootprintDetailExportFilter(
            eventCarbonFootprintId: null === $eventCarbonFootprintId ? null : new EventCarbonFootprintId($eventCarbonFootprintId),
        );
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        $eventCarbonFootprint = $this->eventCarbonFootprint($filter);

        if (null === $eventCarbonFootprint) {
            return;
        }

        foreach ($this->rowsBuilder->build($eventCarbonFootprint) as $row) {
            yield $this->cellsFormatter->cells($row, 'phase', 'phase', self::EXCLUDED_COLUMNS);
        }
    }

    public function isAuthorized(User $user): bool
    {
        $currentRoles = $this->currentRolesProvider->currentRoles($user);

        return null === $currentRoles ? $user->hasRole(Role::Owner) : in_array(Role::Owner, $currentRoles, true);
    }

    public function isAuthorizedForFilter(object $filter, User $user): bool
    {
        $eventCarbonFootprint = $this->eventCarbonFootprint($filter);

        if (null === $eventCarbonFootprint || !$eventCarbonFootprint->isCompleted()) {
            return false;
        }

        return $this->authorizer->canDoThat($eventCarbonFootprint, Action::Export, $user);
    }

    private function eventCarbonFootprint(object $filter): ?EventCarbonFootprint
    {
        if (!$filter instanceof EventCarbonFootprintDetailExportFilter || !$filter->eventCarbonFootprintId instanceof EventCarbonFootprintId) {
            return null;
        }

        return $this->eventCarbonFootprintRepository->find($filter->eventCarbonFootprintId);
    }

    private function string(mixed $value): ?string
    {
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        return trim($value);
    }
}
