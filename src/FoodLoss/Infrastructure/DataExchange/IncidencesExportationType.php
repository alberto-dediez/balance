<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\DataExchange;

use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\FoodLoss\Application\Incidence\Export\IncidencesExportFilter;
use App\FoodLoss\Application\Incidence\IncidenceAuthorizer;
use App\FoodLoss\Domain\Model\Incidence\Incidence;
use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;
use App\FoodLoss\Infrastructure\Symfony\Form\ListIncidencesExportType;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Symfony\Twig\PropertyExtension;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class IncidencesExportationType implements ExportationType
{
    public function __construct(
        private readonly IncidenceRepository $repository,
        private readonly IncidenceAuthorizer $authorizer,
        private readonly TranslatorInterface $translator,
        private readonly PropertyExtension $propertyExtension,
        private readonly LoggedAccount $loggedAccount,
    ) {}

    public function code(): string
    {
        return 'incidences';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Incidencias');
    }

    public function returnRoute(): string
    {
        return 'incidences';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('name', new TranslatableMessage('Nombre')),
            new ExportColumnDefinition('exceptionOccurredOn', new TranslatableMessage('Fecha y hora')),
            new ExportColumnDefinition('incidenceType', new TranslatableMessage('Tipología')),
            new ExportColumnDefinition('propertyId', new TranslatableMessage('Propiedad')),
            new ExportColumnDefinition('description', new TranslatableMessage('Descripción')),
            new ExportColumnDefinition('effect', new TranslatableMessage('Efecto')),
        ];
    }

    public function filterFormClass(): string
    {
        return ListIncidencesExportType::class;
    }

    public function createFilter(): object
    {
        return new IncidencesExportFilter(
            accountId: $this->loggedAccount->__invoke()?->id(),
        );
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof IncidencesExportFilter) {
            return [];
        }

        return [
            'name' => $filter->name,
            'accountId' => $filter->accountId instanceof AccountId ? (string) $filter->accountId : null,
            'propertyId' => $filter->propertyId instanceof PropertyId ? (string) $filter->propertyId : null,
            'dateFrom' => $filter->dateFrom?->format('Y-m-d'),
            'dateTo' => $filter->dateTo?->format('Y-m-d'),
            'orderBy' => $filter->orderBy,
            'orderWay' => $filter->orderWay,
        ];
    }

    public function hydrateFilter(array $criteria): object
    {
        $filter = new IncidencesExportFilter();

        $filter->name = isset($criteria['name']) ? (string) $criteria['name'] : null;
        $filter->accountId = isset($criteria['accountId']) ? new AccountId((string) $criteria['accountId']) : null;
        $filter->propertyId = isset($criteria['propertyId']) ? new PropertyId((string) $criteria['propertyId']) : null;
        $filter->dateFrom = isset($criteria['dateFrom']) ? new \DateTimeImmutable((string) $criteria['dateFrom']) : null;
        $filter->dateTo = isset($criteria['dateTo']) ? new \DateTimeImmutable((string) $criteria['dateTo']) : null;
        $filter->orderBy = isset($criteria['orderBy']) ? (string) $criteria['orderBy'] : 'default';
        $filter->orderWay = isset($criteria['orderWay']) ? (string) $criteria['orderWay'] : 'ASC';
        $filter->limit = 0;

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof IncidencesExportFilter) {
            return;
        }

        $filter->limit = 0;

        foreach ($this->repository->findByFilter($filter) as $incidence) {
            yield [
                'name' => $incidence->name(),
                'exceptionOccurredOn' => $incidence->exceptionOccurredOn()->format('d/m/Y H:i'),
                'incidenceType' => $this->translator->trans(
                    $incidence->incidenceType()->value,
                    [],
                    'incidenceType',
                ),
                'propertyId' => null !== $incidence->propertyId()
                    ? (string) $this->propertyExtension->propertyName($incidence->propertyId())
                    : '',
                'description' => $incidence->description(),
                'effect' => $incidence->effect(),
            ];
        }
    }

    public function isAuthorized(User $user): bool
    {
        $subject = new Incidence(
            id: IncidenceId::generate(),
            accountId: $user->accountId(),
        );

        return $this->authorizer->canDoThat($subject, Action::Export, $user);
    }
}
