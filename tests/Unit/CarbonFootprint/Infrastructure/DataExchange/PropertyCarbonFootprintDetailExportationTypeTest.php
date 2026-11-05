<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprint\Export\CarbonFootprintDetailRowsBuilder;
use App\CarbonFootprint\Application\CarbonFootprintReport\Data\ProviderNameResolver;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Export\PropertyCarbonFootprintDetailExportFilter;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\DataExchange\CarbonFootprintDetailCellsFormatter;
use App\CarbonFootprint\Infrastructure\DataExchange\PropertyCarbonFootprintDetailExportationType;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryProviderRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\Data\OrganizationalCollectorContextTrait;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\CountedActivityReferencesMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CombustibleActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use App\Tests\Unit\Shared\Application\CurrentRoles\StubCurrentRolesProvider;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PropertyCarbonFootprintDetailExportationTypeTest extends TestCase
{
    use OrganizationalCollectorContextTrait;

    private InMemoryPropertyCarbonFootprintRepository $propertyCarbonFootprintRepository;

    private InMemoryProviderRepository $providerRepository;

    private RequestStack $requestStack;

    private PropertyCarbonFootprintDetailExportationType $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->initActivities();
        $this->propertyCarbonFootprintRepository = new InMemoryPropertyCarbonFootprintRepository();
        $this->providerRepository = ProviderRepositoryMother::inMemory();
        $this->requestStack = new RequestStack();

        $this->sut = $this->createSUT(new AccountId('account-id'));
    }

    public function testCreatesAnEmptyFilterWithoutRequest(): void
    {
        $filter = $this->sut->createFilter();

        self::assertInstanceOf(PropertyCarbonFootprintDetailExportFilter::class, $filter);
        self::assertNull($filter->propertyCarbonFootprintId);
    }

    public function testPreselectsTheFootprintFromTheQueryString(): void
    {
        $propertyCarbonFootprintId = (string) PropertyCarbonFootprintId::generate();

        $this->requestStack->push(Request::create(
            '/property_carbon_footprint_detail/export',
            parameters: ['propertyCarbonFootprintId' => $propertyCarbonFootprintId],
        ));

        $filter = $this->sut->createFilter();

        self::assertInstanceOf(PropertyCarbonFootprintDetailExportFilter::class, $filter);
        self::assertSame($propertyCarbonFootprintId, (string) $filter->propertyCarbonFootprintId);
    }

    public function testDehydratesAndHydratesTheFilter(): void
    {
        $propertyCarbonFootprintId = PropertyCarbonFootprintId::generate();

        $criteria = $this->sut->dehydrateFilter(new PropertyCarbonFootprintDetailExportFilter($propertyCarbonFootprintId));

        self::assertSame(['propertyCarbonFootprintId' => (string) $propertyCarbonFootprintId], $criteria);

        $filter = $this->sut->hydrateFilter($criteria);

        self::assertInstanceOf(PropertyCarbonFootprintDetailExportFilter::class, $filter);
        self::assertSame((string) $propertyCarbonFootprintId, (string) $filter->propertyCarbonFootprintId);
    }

    public function testDehydratesAnEmptyFilter(): void
    {
        $criteria = $this->sut->dehydrateFilter(new PropertyCarbonFootprintDetailExportFilter());

        self::assertSame(['propertyCarbonFootprintId' => null], $criteria);

        $filter = $this->sut->hydrateFilter($criteria);

        self::assertInstanceOf(PropertyCarbonFootprintDetailExportFilter::class, $filter);
        self::assertNull($filter->propertyCarbonFootprintId);
    }

    public function testYieldsNoRowsWithoutFootprint(): void
    {
        self::assertSame([], iterator_to_array($this->sut->rows(new PropertyCarbonFootprintDetailExportFilter(), [])));
        self::assertSame([], iterator_to_array($this->sut->rows(new PropertyCarbonFootprintDetailExportFilter(PropertyCarbonFootprintId::generate()), [])));
    }

    public function testYieldsTheTranslatedDetailOfTheFootprint(): void
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered('P-1', (string) PropertyCarbonFootprintId::generate());
        $provider = ProviderMother::fromNameAndId('Logística ACME', (string) ProviderId::generate());
        $this->providerRepository->add($provider);
        $combustible = $this->save(CombustibleActivityMother::registered());
        $reported = $this->save(ReportedEmissionsActivityMother::registered(description: 'Reparto semanal'));
        $commuting = $this->save(ReportedEmissionsActivityMother::registered(description: 'Desplazamientos'));
        $footprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $footprint->addActivity($reported->id(), new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: $provider->id()));
        $footprint->addActivity($commuting->id(), new ActivityPlacement(Scope::Scope3, Source::EmployeeCommuting, fromEmployees: true));
        $this->propertyCarbonFootprintRepository->add($footprint);

        $rows = iterator_to_array($this->sut->rows(new PropertyCarbonFootprintDetailExportFilter($footprint->id()), []));

        self::assertCount(3, $rows);
        self::assertSame(
            array_map(static fn($column): string => $column->key, $this->sut->columnDefinitions()),
            array_keys($rows[0]),
        );
        self::assertSame('scope_1', $rows[0]['stage']);
        self::assertSame('15.0', $rows[0]['emissionsCo2eq']);
        self::assertSame('Logística ACME', $rows[1]['provider']);
        self::assertSame('Empleados', $rows[2]['origin']);
        self::assertArrayHasKey('acquisitionYear', $rows[0]);
        self::assertArrayNotHasKey('property', $rows[0]);
        self::assertArrayNotHasKey('manualCo2eq', $rows[0]);
    }

    public function testOnlyOwnersAreAuthorized(): void
    {
        self::assertTrue($this->sut->isAuthorized(UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'))));
        self::assertFalse($this->sut->isAuthorized(UserMother::adminUser()));
        self::assertFalse($this->sut->isAuthorized(UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id'))));
    }

    public function testAuthorizesAnAdminImpersonatingAnAccount(): void
    {
        $sut = $this->createSUT(new AccountId('account-id'), [Role::Owner, Role::User]);

        self::assertTrue($sut->isAuthorized(UserMother::adminUser()));
    }

    public function testAuthorizesTheOwnerToExportACompletedFootprintOfTheCurrentAccount(): void
    {
        $footprint = $this->completedFootprint('account-id');

        self::assertTrue($this->sut->isAuthorizedForFilter(
            new PropertyCarbonFootprintDetailExportFilter($footprint->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        ));
    }

    public function testDoesNotAuthorizeTheOwnerToExportAFootprintOfAnotherAccount(): void
    {
        $footprint = $this->completedFootprint('other-account');

        self::assertFalse($this->sut->isAuthorizedForFilter(
            new PropertyCarbonFootprintDetailExportFilter($footprint->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        ));
    }

    public function testDoesNotAuthorizeAFootprintThatIsNotCompleted(): void
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered('P-1', (string) PropertyCarbonFootprintId::generate());
        $this->propertyCarbonFootprintRepository->add($footprint);

        self::assertFalse($this->sut->isAuthorizedForFilter(
            new PropertyCarbonFootprintDetailExportFilter($footprint->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        ));
    }

    public function testDoesNotAuthorizeAnInvalidFilter(): void
    {
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));

        self::assertFalse($this->sut->isAuthorizedForFilter(new \stdClass(), $owner));
        self::assertFalse($this->sut->isAuthorizedForFilter(new PropertyCarbonFootprintDetailExportFilter(), $owner));
        self::assertFalse($this->sut->isAuthorizedForFilter(new PropertyCarbonFootprintDetailExportFilter(PropertyCarbonFootprintId::generate()), $owner));
    }

    private function completedFootprint(string $accountId): PropertyCarbonFootprint
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered('P-1', (string) PropertyCarbonFootprintId::generate(), $accountId);
        $footprint->complete(new Emissions());
        $this->propertyCarbonFootprintRepository->add($footprint);

        return $footprint;
    }

    /** @param Role[]|null $currentRoles */
    private function createSUT(AccountId $currentAccountId, ?array $currentRoles = null): PropertyCarbonFootprintDetailExportationType
    {
        $countedActivityReferences = CountedActivityReferencesMother::create();

        return new PropertyCarbonFootprintDetailExportationType(
            $this->propertyCarbonFootprintRepository,
            new PropertyCarbonFootprintAuthorizer(new StubCurrentAccountIdProvider($currentAccountId), new StubCurrentRolesProvider($currentRoles)),
            new CarbonFootprintDetailRowsBuilder(
                $this->activityRepository,
                $countedActivityReferences,
                $this->itemNameResolver(),
                new DerivedSourceEmissionsCalculator($this->activityRepository, $countedActivityReferences),
                new ProviderNameResolver($this->providerRepository),
                PropertyRepositoryMother::inMemory(),
                new InMemoryUserRepository(),
            ),
            new CarbonFootprintDetailCellsFormatter(new FakeTranslator()),
            new StubCurrentRolesProvider($currentRoles),
            $this->requestStack,
        );
    }
}
