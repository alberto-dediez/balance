<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\DataExchange;

use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\FoodLoss\Application\CriticalPoint\CriticalPointAuthorizer;
use App\FoodLoss\Application\CriticalPoint\Export\CriticalPointsExportFilter;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\ListCriticalPointsExportType;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Symfony\Twig\PropertyExtension;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class CriticalPointsExportationType implements ExportationType
{
    public function __construct(
        private readonly CriticalPointRepository $repository,
        private readonly CriticalPointAuthorizer $authorizer,
        private readonly TranslatorInterface $translator,
        private readonly PropertyExtension $propertyExtension,
        private readonly LoggedAccount $loggedAccount,
    ) {}

    public function code(): string
    {
        return 'critical-points';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Puntos Críticos');
    }

    public function returnRoute(): string
    {
        return 'critical_points';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('name', new TranslatableMessage('Nombre')),
            new ExportColumnDefinition('propertyId', new TranslatableMessage('Propiedad')),
            new ExportColumnDefinition('supplyChainStage', new TranslatableMessage('Etapa')),
            new ExportColumnDefinition('registeredAt', new TranslatableMessage('Fecha de registro')),
        ];
    }

    public function filterFormClass(): string
    {
        return ListCriticalPointsExportType::class;
    }

    public function createFilter(): object
    {
        return new CriticalPointsExportFilter(
            accountId: $this->loggedAccount->__invoke()?->id(),
        );
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof CriticalPointsExportFilter) {
            return [];
        }

        return [
            'name' => $filter->name,
            'accountId' => $filter->accountId instanceof AccountId ? (string) $filter->accountId : null,
            'propertyId' => $filter->propertyId instanceof PropertyId ? (string) $filter->propertyId : null,
            'orderBy' => $filter->orderBy,
            'orderWay' => $filter->orderWay,
        ];
    }

    public function hydrateFilter(array $criteria): object
    {
        $filter = new CriticalPointsExportFilter();

        $filter->name = isset($criteria['name']) ? (string) $criteria['name'] : null;
        $filter->accountId = isset($criteria['accountId']) ? new AccountId((string) $criteria['accountId']) : null;
        $filter->propertyId = isset($criteria['propertyId']) ? new PropertyId((string) $criteria['propertyId']) : null;
        $filter->orderBy = isset($criteria['orderBy']) ? (string) $criteria['orderBy'] : 'default';
        $filter->orderWay = isset($criteria['orderWay']) ? (string) $criteria['orderWay'] : 'ASC';
        $filter->limit = 0;

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof CriticalPointsExportFilter) {
            return;
        }

        $filter->limit = 0;

        foreach ($this->repository->findByFilter($filter) as $criticalPoint) {
            yield [
                'name' => $criticalPoint->name(),
                'propertyId' => null !== $criticalPoint->propertyId()
                    ? (string) $this->propertyExtension->propertyName($criticalPoint->propertyId())
                    : '',
                'supplyChainStage' => $this->translator->trans(
                    $criticalPoint->supplyChainStage()->value,
                    [],
                    'supplyChainStage',
                ),
                'registeredAt' => $criticalPoint->registeredAt()->format('d/m/Y'),
            ];
        }
    }

    public function isAuthorized(User $user): bool
    {
        $subject = new CriticalPoint(
            id: CriticalPointId::generate(),
            accountId: $user->accountId(),
        );

        return $this->authorizer->canDoThat($subject, Action::Export, $user);
    }
}
