<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Infrastructure\DataExchange;

use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\Property\Application\DistributorAssignment\DistributedPropertiesPolicy;
use App\Property\Application\Property\Export\PropertiesExportFilter;
use App\Property\Application\Property\List\DistributorPropertiesScope;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Infrastructure\DataExchange\PropertiesExportationType;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCountryRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryDestinationRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Application\Account\DistributorPolicy;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyMother;
use App\Tests\Unit\Property\Domain\Model\Country\CountryMother;
use App\Tests\Unit\Property\Domain\Model\Destination\DestinationMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

class PropertiesExportationTypeTest extends TestCase
{
    private InMemoryPropertyRepository $propertyRepository;

    private InMemoryAccountRepository $accountRepository;

    private InMemoryDestinationRepository $destinationRepository;

    private InMemoryCountryRepository $countryRepository;

    private InMemoryCompanyRepository $companyRepository;

    private RequestStack $requestStack;

    private PropertiesExportationType $sut;

    protected function setUp(): void
    {
        $this->propertyRepository = new InMemoryPropertyRepository();
        $this->destinationRepository = new InMemoryDestinationRepository();
        $this->countryRepository = new InMemoryCountryRepository();
        $this->companyRepository = new InMemoryCompanyRepository();
        $this->requestStack = new RequestStack();

        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');
        $other = AccountMother::fromNameAndId('Other', 'other');
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $noFeatures = AccountMother::distributorAccountFromNameAndId('Distributor without features', 'distributor-no-features');
        $noFeatures->changeFeatures(new AccountFeatures(
            events: false,
            organizationalCarbonFootprint: false,
            propertyTripCalculators: false,
        ));

        $this->accountRepository = new InMemoryAccountRepository([
            'hotel' => $hotel,
            'other' => $other,
            'distributor' => $distributor,
            'distributor-no-features' => $noFeatures,
        ]);

        $this->sut = $this->sutWithCurrentAccount(new AccountId('hotel'));
    }

    public function testCreatesAFilterWithTheCurrentAccountWithoutRequest(): void
    {
        $filter = $this->sut->createFilter();

        self::assertInstanceOf(PropertiesExportFilter::class, $filter);
        self::assertSame('hotel', (string) $filter->accountId);
        self::assertNull($filter->name);
        self::assertNull($filter->destinationId);
        self::assertNull($filter->countryId);
        self::assertNull($filter->operatingRegime);
    }

    public function testFillsTheFilterFromTheQueryString(): void
    {
        $this->requestStack->push(Request::create('/properties/export', parameters: [
            'name' => 'Hotel',
            'destinationId' => 'destination-id',
            'countryId' => 'country-id',
            'operatingRegime' => OperatingRegime::Franchised->value,
            'orderBy' => 'registeredAt',
            'orderWay' => 'DESC',
        ]));

        $filter = $this->sut->createFilter();

        self::assertInstanceOf(PropertiesExportFilter::class, $filter);
        self::assertSame('Hotel', $filter->name);
        self::assertSame('destination-id', (string) $filter->destinationId);
        self::assertSame('country-id', (string) $filter->countryId);
        self::assertSame(OperatingRegime::Franchised, $filter->operatingRegime);
        self::assertSame('registeredAt', $filter->orderBy);
        self::assertSame('DESC', $filter->orderWay);
    }

    public function testIgnoresEmptyAndInvalidQueryStringValues(): void
    {
        $this->requestStack->push(Request::create('/properties/export', parameters: [
            'name' => ' ',
            'operatingRegime' => 'unknown',
            'orderBy' => 'accountId',
            'orderWay' => 'sideways',
        ]));

        $filter = $this->sut->createFilter();

        self::assertInstanceOf(PropertiesExportFilter::class, $filter);
        self::assertNull($filter->name);
        self::assertNull($filter->operatingRegime);
        self::assertSame('default', $filter->orderBy);
        self::assertSame('ASC', $filter->orderWay);
    }

    public function testDehydratesAndHydratesTheFilter(): void
    {
        $filter = new PropertiesExportFilter(
            name: 'Hotel',
            destinationId: new DestinationId('destination-id'),
            countryId: new CountryId('country-id'),
            accountId: new AccountId('hotel'),
            operatingRegime: OperatingRegime::LeasedOut,
        );
        $filter->orderBy = 'name';
        $filter->orderWay = 'DESC';

        $criteria = $this->sut->dehydrateFilter($filter);

        self::assertSame([
            'name' => 'Hotel',
            'destinationId' => 'destination-id',
            'countryId' => 'country-id',
            'operatingRegime' => OperatingRegime::LeasedOut->value,
            'accountId' => 'hotel',
            'orderBy' => 'name',
            'orderWay' => 'DESC',
        ], $criteria);

        $hydrated = $this->sut->hydrateFilter($criteria);

        self::assertInstanceOf(PropertiesExportFilter::class, $hydrated);
        self::assertSame('Hotel', $hydrated->name);
        self::assertSame('destination-id', (string) $hydrated->destinationId);
        self::assertSame('country-id', (string) $hydrated->countryId);
        self::assertSame('hotel', (string) $hydrated->accountId);
        self::assertSame(OperatingRegime::LeasedOut, $hydrated->operatingRegime);
        self::assertSame('name', $hydrated->orderBy);
        self::assertSame('DESC', $hydrated->orderWay);
        self::assertSame(0, $hydrated->limit);
    }

    public function testRowsReturnEveryPropertyOfTheAccountWithoutPaginating(): void
    {
        for ($i = 1; $i <= 12; ++$i) {
            $this->propertyRepository->add(PropertyMother::from(PropertyId::generate(), 'Own ' . $i, new AccountId('hotel')));
        }

        $this->propertyRepository->add(PropertyMother::from(PropertyId::generate(), 'Foreign', new AccountId('other')));

        $rows = iterator_to_array($this->sut->rows(new PropertiesExportFilter(accountId: new AccountId('hotel')), []), false);

        self::assertCount(12, $rows);
        self::assertNotContains('Foreign', array_column($rows, 'name'));
    }

    public function testRowsAreFilteredByName(): void
    {
        $this->propertyRepository->add(PropertyMother::from(PropertyId::generate(), 'Sea view', new AccountId('hotel')));
        $this->propertyRepository->add(PropertyMother::from(PropertyId::generate(), 'Mountain', new AccountId('hotel')));

        $rows = iterator_to_array($this->sut->rows(new PropertiesExportFilter(name: 'Sea', accountId: new AccountId('hotel')), []), false);

        self::assertSame(['Sea view'], array_column($rows, 'name'));
    }

    public function testRowsAreFormattedLikeTheList(): void
    {
        $country = CountryMother::fromNameAndId('Spain', 'country-id');
        $destination = DestinationMother::fromNameAndCountryAndId('Tenerife', 'country-id', 'destination-id');
        $company = CompanyMother::fromNameAndId('Chain', 'company-id');
        $this->countryRepository->add($country);
        $this->destinationRepository->add($destination);
        $this->companyRepository->add($company);

        $property = PropertyMother::from(
            PropertyId::generate(),
            'Complete',
            new AccountId('hotel'),
            new PropertyInfo(destinationId: $destination->id(), countryId: $country->id()),
            companyId: new CompanyId('company-id'),
        );
        $this->propertyRepository->add($property);

        $rows = iterator_to_array($this->sut->rows(new PropertiesExportFilter(accountId: new AccountId('hotel')), []), false);

        self::assertSame([[
            'name' => 'Complete',
            'account' => 'Hotel',
            'destination' => 'Tenerife',
            'country' => 'Spain',
            'company' => 'Chain',
            'operatingRegime' => 'Propiedad',
            'registeredAt' => $property->registeredAt()->format('d/m/Y'),
        ]], $rows);
    }

    public function testRowsAreEmptyWhenDestinationCountryAndCompanyAreMissing(): void
    {
        $this->propertyRepository->add(PropertyMother::from(PropertyId::generate(), 'Bare', new AccountId('hotel')));

        $rows = iterator_to_array($this->sut->rows(new PropertiesExportFilter(accountId: new AccountId('hotel')), []), false);

        self::assertSame('', $rows[0]['destination']);
        self::assertSame('', $rows[0]['country']);
        self::assertSame('', $rows[0]['company']);
    }

    public function testRowsOfADistributorIncludeDistributedPropertiesWithTheirAccount(): void
    {
        $this->propertyRepository->add(PropertyMother::from(PropertyId::generate(), 'Own', new AccountId('distributor')));
        $this->propertyRepository->add(PropertyMother::from(PropertyId::generate(), 'Distributed', new AccountId('hotel')));
        $this->propertyRepository->add(PropertyMother::from(PropertyId::generate(), 'Foreign', new AccountId('other')));

        $sut = $this->sutWithCurrentAccount(new AccountId('distributor'), [
            DistributorAssignmentMother::forAccount(new AccountId('distributor'), new AccountId('hotel')),
        ]);

        $rows = iterator_to_array($sut->rows(new PropertiesExportFilter(accountId: new AccountId('distributor')), []), false);
        $accountsByName = array_column($rows, 'account', 'name');
        ksort($accountsByName);

        self::assertSame(['Distributed' => 'Hotel', 'Own' => 'Greenhost'], $accountsByName);
    }

    public function testAccountColumnIsSelectedByDefaultOnlyForDistributors(): void
    {
        self::assertFalse($this->accountColumn($this->sut)->selectedByDefault);
        self::assertTrue($this->accountColumn($this->sutWithCurrentAccount(new AccountId('distributor')))->selectedByDefault);
    }

    public function testAuthorizesOwnersWithPropertyFeatures(): void
    {
        self::assertTrue($this->sut->isAuthorized($this->owner('hotel')));
    }

    public function testAuthorizesDistributorOwnersWithoutPropertyFeatures(): void
    {
        $sut = $this->sutWithCurrentAccount(new AccountId('distributor-no-features'));

        self::assertTrue($sut->isAuthorized($this->owner('distributor-no-features')));
    }

    public function testDoesNotAuthorizeNormalUsers(): void
    {
        self::assertFalse($this->sut->isAuthorized(UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('hotel'))));
    }

    public function testAuthorizesTheFilterOnlyForTheCurrentAccount(): void
    {
        $owner = $this->owner('hotel');

        self::assertTrue($this->sut->isAuthorizedForFilter(new PropertiesExportFilter(accountId: new AccountId('hotel')), $owner));
        self::assertFalse($this->sut->isAuthorizedForFilter(new PropertiesExportFilter(accountId: new AccountId('other')), $owner));
        self::assertFalse($this->sut->isAuthorizedForFilter(new PropertiesExportFilter(), $owner));
    }

    public function testAuthorizesAdminsForAnyAccount(): void
    {
        self::assertTrue($this->sut->isAuthorizedForFilter(
            new PropertiesExportFilter(accountId: new AccountId('other')),
            UserMother::adminUser(),
        ));
    }

    private function accountColumn(PropertiesExportationType $sut): ExportColumnDefinition
    {
        foreach ($sut->columnDefinitions() as $column) {
            if ('account' === $column->key) {
                return $column;
            }
        }

        self::fail('Account column not found');
    }

    private function owner(string $accountId): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId($accountId));
    }

    /**
     * @param list<DistributorAssignment> $assignments
     */
    private function sutWithCurrentAccount(AccountId $currentAccountId, array $assignments = []): PropertiesExportationType
    {
        $currentAccountIdProvider = new StubCurrentAccountIdProvider($currentAccountId);
        $accountFeaturesPolicy = new AccountFeaturesPolicy($this->accountRepository);
        $distributorPolicy = new DistributorPolicy($this->accountRepository);
        $distributedPropertiesPolicy = new DistributedPropertiesPolicy(
            DistributorAssignmentRepositoryMother::inMemory($assignments),
            $this->propertyRepository,
            $distributorPolicy,
            $accountFeaturesPolicy,
        );

        $translator = new Translator('es');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', [OperatingRegime::Owned->value => 'Propiedad'], 'es', 'operatingRegime');

        return new PropertiesExportationType(
            $this->propertyRepository,
            new PropertyAuthorizer(
                currentAccountIdProvider: $currentAccountIdProvider,
                accountFeaturesPolicy: $accountFeaturesPolicy,
                distributedPropertiesPolicy: $distributedPropertiesPolicy,
                distributorPolicy: $distributorPolicy,
            ),
            new DistributorPropertiesScope($distributedPropertiesPolicy, $distributorPolicy),
            $distributorPolicy,
            $currentAccountIdProvider,
            $this->requestStack,
            $translator,
            $this->accountRepository,
            $this->destinationRepository,
            $this->countryRepository,
            $this->companyRepository,
        );
    }
}
