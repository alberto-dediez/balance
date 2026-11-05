<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\DataExchange;

use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\DataExchange\Domain\Model\Exportation\FilterAuthorizedExportationType;
use App\Property\Application\Property\Export\PropertiesExportFilter;
use App\Property\Application\Property\List\DistributorPropertiesScope;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Company\Company;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Company\CompanyRepository;
use App\Property\Domain\Model\Country\Country;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Property\Domain\Model\Destination\Destination;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Property\Infrastructure\Symfony\Form\PropertiesExportType;
use App\Security\Application\Account\DistributorPolicy;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class PropertiesExportationType implements FilterAuthorizedExportationType
{
    public function __construct(
        private readonly PropertyRepository $propertyRepository,
        private readonly PropertyAuthorizer $authorizer,
        private readonly DistributorPropertiesScope $distributorPropertiesScope,
        private readonly DistributorPolicy $distributorPolicy,
        private readonly CurrentAccountIdProvider $currentAccountIdProvider,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly AccountRepository $accountRepository,
        private readonly DestinationRepository $destinationRepository,
        private readonly CountryRepository $countryRepository,
        private readonly CompanyRepository $companyRepository,
    ) {}

    public function code(): string
    {
        return 'properties';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Propiedades');
    }

    public function returnRoute(): string
    {
        return 'properties';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('name', new TranslatableMessage('Nombre')),
            new ExportColumnDefinition('account', new TranslatableMessage('Cuenta'), $this->currentAccountIsDistributor()),
            new ExportColumnDefinition('destination', new TranslatableMessage('Destino')),
            new ExportColumnDefinition('country', new TranslatableMessage('País')),
            new ExportColumnDefinition('company', new TranslatableMessage('Empresa')),
            new ExportColumnDefinition('operatingRegime', new TranslatableMessage('Régimen de explotación')),
            new ExportColumnDefinition('registeredAt', new TranslatableMessage('Fecha de registro')),
        ];
    }

    public function filterFormClass(): string
    {
        return PropertiesExportType::class;
    }

    public function createFilter(): object
    {
        $filter = new PropertiesExportFilter(accountId: $this->currentAccountIdProvider->currentAccountId());

        $query = $this->requestStack->getCurrentRequest()?->query;

        if (null === $query) {
            return $filter;
        }

        $filter->name = $this->string($query->get('name'));

        $destinationId = $this->string($query->get('destinationId'));
        $filter->destinationId = null === $destinationId ? null : new DestinationId($destinationId);

        $countryId = $this->string($query->get('countryId'));
        $filter->countryId = null === $countryId ? null : new CountryId($countryId);

        $operatingRegime = $this->string($query->get('operatingRegime'));
        $filter->operatingRegime = null === $operatingRegime ? null : OperatingRegime::tryFrom($operatingRegime);

        $orderBy = $this->string($query->get('orderBy'));

        if (in_array($orderBy, ['name', 'registeredAt'], true)) {
            $filter->orderBy = $orderBy;
        }

        $orderWay = $this->string($query->get('orderWay'));

        if (in_array($orderWay, ['ASC', 'DESC'], true)) {
            $filter->orderWay = $orderWay;
        }

        return $filter;
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof PropertiesExportFilter) {
            return [];
        }

        return [
            'name' => $filter->name,
            'destinationId' => $filter->destinationId instanceof DestinationId ? (string) $filter->destinationId : null,
            'countryId' => $filter->countryId instanceof CountryId ? (string) $filter->countryId : null,
            'operatingRegime' => $filter->operatingRegime?->value,
            'accountId' => $filter->accountId instanceof AccountId ? (string) $filter->accountId : null,
            'orderBy' => $filter->orderBy,
            'orderWay' => $filter->orderWay,
        ];
    }

    public function hydrateFilter(array $criteria): object
    {
        $destinationId = $this->string($criteria['destinationId'] ?? null);
        $countryId = $this->string($criteria['countryId'] ?? null);
        $operatingRegime = $this->string($criteria['operatingRegime'] ?? null);
        $accountId = $this->string($criteria['accountId'] ?? null);

        $filter = new PropertiesExportFilter(
            name: $this->string($criteria['name'] ?? null),
            destinationId: null === $destinationId ? null : new DestinationId($destinationId),
            countryId: null === $countryId ? null : new CountryId($countryId),
            accountId: null === $accountId ? null : new AccountId($accountId),
            operatingRegime: null === $operatingRegime ? null : OperatingRegime::tryFrom($operatingRegime),
            limit: 0,
        );

        $filter->orderBy = $this->string($criteria['orderBy'] ?? null) ?? 'default';
        $filter->orderWay = $this->string($criteria['orderWay'] ?? null) ?? 'ASC';

        return $filter;
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof PropertiesExportFilter) {
            return;
        }

        $scopedFilter = clone $filter;
        $scopedFilter->limit = 0;

        $this->distributorPropertiesScope->expand($scopedFilter);

        /** @var array<string, ?Account> $accounts */
        $accounts = [];
        /** @var array<string, ?Destination> $destinations */
        $destinations = [];
        /** @var array<string, ?Country> $countries */
        $countries = [];
        /** @var array<string, ?Company> $companies */
        $companies = [];

        foreach ($this->propertyRepository->findByFilter($scopedFilter) as $property) {
            $accountId = $property->accountId();
            $destinationId = $property->info()->destinationId();
            $countryId = $property->info()->countryId();
            $companyId = $property->companyId();

            if ($accountId instanceof AccountId && !array_key_exists((string) $accountId, $accounts)) {
                $accounts[(string) $accountId] = $this->accountRepository->find($accountId);
            }

            if ($destinationId instanceof DestinationId && !array_key_exists((string) $destinationId, $destinations)) {
                $destinations[(string) $destinationId] = $this->destinationRepository->find($destinationId);
            }

            if ($countryId instanceof CountryId && !array_key_exists((string) $countryId, $countries)) {
                $countries[(string) $countryId] = $this->countryRepository->find($countryId);
            }

            if ($companyId instanceof CompanyId && !array_key_exists((string) $companyId, $companies)) {
                $companies[(string) $companyId] = $this->companyRepository->find($companyId);
            }

            yield [
                'name' => $property->name(),
                'account' => $accountId instanceof AccountId ? (string) ($accounts[(string) $accountId] ?? '') : '',
                'destination' => $destinationId instanceof DestinationId ? (string) ($destinations[(string) $destinationId] ?? '') : '',
                'country' => $countryId instanceof CountryId ? (string) ($countries[(string) $countryId] ?? '') : '',
                'company' => $companyId instanceof CompanyId ? (string) ($companies[(string) $companyId] ?? '') : '',
                'operatingRegime' => $this->translator->trans($property->operatingRegime()->value, [], 'operatingRegime'),
                'registeredAt' => $property->registeredAt()->format('d/m/Y'),
            ];
        }
    }

    public function isAuthorized(User $user): bool
    {
        $subject = new Property(
            id: PropertyId::generate(),
            accountId: $this->currentAccountIdProvider->currentAccountId(),
        );

        return $this->authorizer->canDoThat($subject, Action::List, $user);
    }

    public function isAuthorizedForFilter(object $filter, User $user): bool
    {
        if (!$filter instanceof PropertiesExportFilter || !$filter->accountId instanceof AccountId) {
            return false;
        }

        $subject = new Property(
            id: PropertyId::generate(),
            accountId: $filter->accountId,
        );

        return $this->authorizer->canDoThat($subject, Action::List, $user);
    }

    private function currentAccountIsDistributor(): bool
    {
        $accountId = $this->currentAccountIdProvider->currentAccountId();

        return $accountId instanceof AccountId && $this->distributorPolicy->isActiveDistributor($accountId);
    }

    private function string(mixed $value): ?string
    {
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        return trim($value);
    }
}
