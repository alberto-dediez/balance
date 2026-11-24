<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\DataExchange;

use App\Balance\Application\ProjectContribution\Export\ProjectContributionsExportFilter;
use App\Balance\Application\ProjectContribution\List\ProjectContributionsListScope;
use App\Balance\Application\ProjectContribution\ProjectContributionAuthorizer;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;
use App\Balance\Infrastructure\Symfony\Form\ProjectContributionsExportType;
use App\Balance\Infrastructure\Symfony\Twig\ProjectExtension;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\DataExchange\Domain\Model\Exportation\FilterAuthorizedExportationType;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Account\DistributorPolicy;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProjectContributionsExportationType implements FilterAuthorizedExportationType
{
    /** @var array<string, string> */
    private array $accountNames = [];

    public function __construct(
        private readonly ProjectContributionRepository $projectContributionRepository,
        private readonly AccountRepository $accountRepository,
        private readonly ProjectContributionAuthorizer $authorizer,
        private readonly ProjectContributionsListScope $listScope,
        private readonly DistributorPolicy $distributorPolicy,
        private readonly ProjectExtension $projectExtension,
        private readonly TranslatorInterface $translator,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly ?CurrentAccountIdProvider $currentAccountIdProvider = null,
    ) {}

    public function code(): string
    {
        return 'project_contributions';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Aportaciones a proyectos');
    }

    public function returnRoute(): string
    {
        return $this->canExportEveryAccount() ? 'admin_project_contributions' : 'project_contributions';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('registeredAt', new TranslatableMessage('Fecha')),
            new ExportColumnDefinition('paidAt', new TranslatableMessage('Fecha de pago')),
            new ExportColumnDefinition('account', new TranslatableMessage('Cuenta')),
            new ExportColumnDefinition('project', new TranslatableMessage('Proyecto')),
            new ExportColumnDefinition('origin', new TranslatableMessage('Origen')),
            new ExportColumnDefinition('originName', new TranslatableMessage('Detalle del origen')),
            new ExportColumnDefinition('status', new TranslatableMessage('Estado')),
            new ExportColumnDefinition('tonnes', new TranslatableMessage('Toneladas')),
            new ExportColumnDefinition('compensatedEmissions', new TranslatableMessage('Emisiones compensadas (kg CO₂eq)')),
            new ExportColumnDefinition('freeAmount', new TranslatableMessage('Aportación libre')),
            new ExportColumnDefinition('pricePerTonne', new TranslatableMessage('Precio por tonelada')),
            new ExportColumnDefinition('contributionAmount', new TranslatableMessage('Aportación al proyecto')),
            new ExportColumnDefinition('managementFeeAmount', new TranslatableMessage('Margen de gestión')),
            new ExportColumnDefinition('taxableAmount', new TranslatableMessage('Base imponible')),
            new ExportColumnDefinition('vatRate', new TranslatableMessage('IVA %')),
            new ExportColumnDefinition('vatAmount', new TranslatableMessage('IVA')),
            new ExportColumnDefinition('totalAmount', new TranslatableMessage('Total')),
            new ExportColumnDefinition('currency', new TranslatableMessage('Divisa')),
            new ExportColumnDefinition('contributorName', new TranslatableMessage('Nombre')),
            new ExportColumnDefinition('contributorEmail', new TranslatableMessage('Email')),
            new ExportColumnDefinition('contributorCountry', new TranslatableMessage('País')),
            new ExportColumnDefinition('contributorIsCompany', new TranslatableMessage('Empresa')),
            new ExportColumnDefinition('contributorCompanyName', new TranslatableMessage('Razón social')),
            new ExportColumnDefinition('contributorVatNumber', new TranslatableMessage('NIF / NIF-IVA')),
        ];
    }

    public function filterFormClass(): string
    {
        return ProjectContributionsExportType::class;
    }

    public function createFilter(): object
    {
        $filter = new ProjectContributionsExportFilter();

        if ($this->canExportEveryAccount()) {
            return $filter;
        }

        $filter->accountId = $this->currentAccountIdProvider?->currentAccountId();
        $filter->viewerAccountId = $filter->accountId;

        return $filter;
    }

    public function canExportEveryAccount(): bool
    {
        return $this->authorizationChecker->isGranted(
            Action::Admin->value,
            new ProjectContribution(id: ProjectContributionId::generate()),
        );
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof ProjectContributionsExportFilter) {
            return [];
        }

        return [
            'accountId' => $filter->accountId instanceof AccountId ? (string) $filter->accountId : null,
            'viewerAccountId' => $filter->viewerAccountId instanceof AccountId ? (string) $filter->viewerAccountId : null,
            'projectId' => $filter->projectId instanceof ProjectId ? (string) $filter->projectId : null,
            'propertyId' => $filter->propertyId instanceof PropertyId ? (string) $filter->propertyId : null,
            'status' => $filter->status?->value,
            'from' => $filter->from?->format('Y-m-d'),
            'to' => $filter->to?->format('Y-m-d'),
            'orderBy' => $filter->orderBy,
            'orderWay' => $filter->orderWay,
        ];
    }

    public function hydrateFilter(array $criteria): object
    {
        $filter = new ProjectContributionsExportFilter();

        $filter->accountId = isset($criteria['accountId']) ? new AccountId((string) $criteria['accountId']) : null;
        $filter->viewerAccountId = isset($criteria['viewerAccountId']) ? new AccountId((string) $criteria['viewerAccountId']) : null;
        $filter->projectId = isset($criteria['projectId']) ? new ProjectId((string) $criteria['projectId']) : null;
        $filter->propertyId = isset($criteria['propertyId']) ? new PropertyId((string) $criteria['propertyId']) : null;
        $filter->status = isset($criteria['status']) ? ProjectContributionStatus::from((string) $criteria['status']) : null;
        $filter->from = $this->date($criteria['from'] ?? null);
        $filter->to = $this->date($criteria['to'] ?? null);
        $filter->orderBy = isset($criteria['orderBy']) ? (string) $criteria['orderBy'] : 'default';
        $filter->orderWay = isset($criteria['orderWay']) ? (string) $criteria['orderWay'] : 'ASC';
        $filter->limit = 0;

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof ProjectContributionsExportFilter) {
            return;
        }

        $query = clone $filter;
        $query->limit = 0;
        $viewerAccountId = $filter->viewerAccountId;

        if ($viewerAccountId instanceof AccountId) {
            $query->accountId = $viewerAccountId;
            $this->listScope->apply($query, $this->distributorPolicy->isActiveDistributor($viewerAccountId));
        }

        foreach ($this->projectContributionRepository->findByFilter($query) as $projectContribution) {
            yield $this->row($projectContribution, $viewerAccountId);
        }
    }

    public function isAuthorized(User $user): bool
    {
        $subject = new ProjectContribution(
            id: ProjectContributionId::generate(),
            accountId: $this->currentAccountIdProvider?->currentAccountId() ?? $user->accountId(),
        );

        return $this->authorizer->canDoThat($subject, Action::Export, $user);
    }

    public function isAuthorizedForFilter(object $filter, User $user): bool
    {
        if (!$filter instanceof ProjectContributionsExportFilter) {
            return false;
        }

        $viewerAccountId = $filter->viewerAccountId;

        if ($viewerAccountId instanceof AccountId
            && (!$filter->accountId instanceof AccountId || !$filter->accountId->equals($viewerAccountId))) {
            return false;
        }

        $subject = new ProjectContribution(
            id: ProjectContributionId::generate(),
            accountId: $viewerAccountId,
        );

        if (!$viewerAccountId instanceof AccountId) {
            return $this->authorizer->canDoThat($subject, Action::Admin, $user);
        }

        return $this->authorizer->canDoThat($subject, Action::Export, $user);
    }

    /** @return array<string, scalar|null> */
    private function row(ProjectContribution $projectContribution, ?AccountId $viewerAccountId): array
    {
        $accountId = $projectContribution->accountId();
        $projectId = $projectContribution->projectId();
        $isOwnContribution = !$viewerAccountId instanceof AccountId
            || ($accountId instanceof AccountId && $accountId->equals($viewerAccountId));

        return [
            'registeredAt' => $projectContribution->registeredAt()->format('d/m/Y H:i'),
            'paidAt' => $projectContribution->paidAt()?->format('d/m/Y H:i') ?? '',
            'account' => $accountId instanceof AccountId ? $this->accountName($accountId) : '',
            'project' => $projectId instanceof ProjectId ? $this->projectExtension->projectName($projectId)?->name() ?? '' : '',
            'origin' => $this->translator->trans($projectContribution->origin()->value, [], 'projectContributionOrigin'),
            'originName' => $projectContribution->originName() ?? '',
            'status' => $this->translator->trans($projectContribution->status()->value, [], 'projectContributionStatus'),
            'tonnes' => round($projectContribution->tonnes(), 4),
            'compensatedEmissions' => null === $projectContribution->co2eqKg() ? '' : round($projectContribution->co2eqKg(), 3),
            'freeAmount' => $this->yesNo($projectContribution->isFreeAmount()),
            'pricePerTonne' => round($projectContribution->pricePerTonne(), 2),
            'contributionAmount' => round($projectContribution->contributionAmount(), 2),
            'managementFeeAmount' => round($projectContribution->managementFeeAmount(), 2),
            'taxableAmount' => round($projectContribution->taxableAmount(), 2),
            'vatRate' => round($projectContribution->vatRate(), 2),
            'vatAmount' => round($projectContribution->vatAmount(), 2),
            'totalAmount' => round($projectContribution->totalAmount(), 2),
            'currency' => $projectContribution->currency(),
            'contributorName' => $projectContribution->contributorName(),
            'contributorEmail' => $isOwnContribution ? $projectContribution->contributorEmail() : '',
            'contributorCountry' => $projectContribution->contributorCountry(),
            'contributorIsCompany' => $this->yesNo($projectContribution->contributorIsCompany()),
            'contributorCompanyName' => $projectContribution->contributorCompanyName() ?? '',
            'contributorVatNumber' => $projectContribution->contributorVatNumber() ?? '',
        ];
    }

    private function accountName(AccountId $accountId): string
    {
        if (!isset($this->accountNames[(string) $accountId])) {
            $this->accountNames[(string) $accountId] = $this->accountRepository->find($accountId)?->name() ?? '';
        }

        return $this->accountNames[(string) $accountId];
    }

    private function yesNo(bool $value): string
    {
        return $value ? $this->translator->trans('Sí') : $this->translator->trans('No');
    }

    private function date(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || '' === $value) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return false === $date ? null : $date;
    }
}
