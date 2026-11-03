<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprint\Export\CarbonFootprintDetailRowsBuilder;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Export\CompanyCarbonFootprintDetailExportFilter;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Infrastructure\DataExchange\CarbonFootprintDetailCellsFormatter;
use App\CarbonFootprint\Infrastructure\DataExchange\CompanyCarbonFootprintDetailExportationType;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
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
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
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

class CompanyCarbonFootprintDetailExportationTypeTest extends TestCase
{
    use OrganizationalCollectorContextTrait;

    private InMemoryCompanyCarbonFootprintRepository $companyCarbonFootprintRepository;

    private InMemoryProviderRepository $providerRepository;

    private RequestStack $requestStack;

    private CompanyCarbonFootprintDetailExportationType $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->initActivities();
        $this->companyCarbonFootprintRepository = new InMemoryCompanyCarbonFootprintRepository();
        $this->providerRepository = ProviderRepositoryMother::inMemory();
        $this->requestStack = new RequestStack();

        $this->sut = $this->createSUT(new AccountId('account-id'));
    }

    public function testCreatesAnEmptyFilterWithoutRequest(): void
    {
        $filter = $this->sut->createFilter();

        self::assertInstanceOf(CompanyCarbonFootprintDetailExportFilter::class, $filter);
        self::assertNull($filter->companyCarbonFootprintId);
    }

    public function testPreselectsTheFootprintFromTheQueryString(): void
    {
        $companyCarbonFootprintId = (string) CompanyCarbonFootprintId::generate();

        $this->requestStack->push(Request::create(
            '/company_carbon_footprint_detail/export',
            parameters: ['companyCarbonFootprintId' => $companyCarbonFootprintId],
        ));

        $filter = $this->sut->createFilter();

        self::assertInstanceOf(CompanyCarbonFootprintDetailExportFilter::class, $filter);
        self::assertSame($companyCarbonFootprintId, (string) $filter->companyCarbonFootprintId);
    }

    public function testDehydratesAndHydratesTheFilter(): void
    {
        $companyCarbonFootprintId = CompanyCarbonFootprintId::generate();

        $criteria = $this->sut->dehydrateFilter(new CompanyCarbonFootprintDetailExportFilter($companyCarbonFootprintId));

        self::assertSame(['companyCarbonFootprintId' => (string) $companyCarbonFootprintId], $criteria);

        $filter = $this->sut->hydrateFilter($criteria);

        self::assertInstanceOf(CompanyCarbonFootprintDetailExportFilter::class, $filter);
        self::assertSame((string) $companyCarbonFootprintId, (string) $filter->companyCarbonFootprintId);
    }

    public function testDehydratesAnEmptyFilter(): void
    {
        $criteria = $this->sut->dehydrateFilter(new CompanyCarbonFootprintDetailExportFilter());

        self::assertSame(['companyCarbonFootprintId' => null], $criteria);

        $filter = $this->sut->hydrateFilter($criteria);

        self::assertInstanceOf(CompanyCarbonFootprintDetailExportFilter::class, $filter);
        self::assertNull($filter->companyCarbonFootprintId);
    }

    public function testYieldsNoRowsWithoutFootprint(): void
    {
        self::assertSame([], iterator_to_array($this->sut->rows(new CompanyCarbonFootprintDetailExportFilter(), [])));
        self::assertSame([], iterator_to_array($this->sut->rows(new CompanyCarbonFootprintDetailExportFilter(CompanyCarbonFootprintId::generate()), [])));
    }

    public function testYieldsTheTranslatedDetailOfTheFootprint(): void
    {
        $footprint = CompanyCarbonFootprintMother::withEmployeesAnswered('C-1', (string) CompanyCarbonFootprintId::generate());
        $provider = ProviderMother::fromNameAndId('Logística ACME', (string) ProviderId::generate());
        $this->providerRepository->add($provider);
        $combustible = $this->save(CombustibleActivityMother::registered());
        $reported = $this->save(ReportedEmissionsActivityMother::registered(description: 'Reparto semanal'));
        $commuting = $this->save(ReportedEmissionsActivityMother::registered(description: 'Desplazamientos'));
        $footprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $footprint->addActivity($reported->id(), new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: $provider->id()));
        $footprint->addActivity($commuting->id(), new ActivityPlacement(Scope::Scope3, Source::EmployeeCommuting, fromEmployees: true));
        $this->companyCarbonFootprintRepository->add($footprint);

        $rows = iterator_to_array($this->sut->rows(new CompanyCarbonFootprintDetailExportFilter($footprint->id()), []));

        self::assertCount(3, $rows);
        self::assertSame(
            array_map(static fn($column): string => $column->key, $this->sut->columnDefinitions()),
            array_keys($rows[0]),
        );
        self::assertSame('scope_1', $rows[0]['stage']);
        self::assertSame('15.0', $rows[0]['emissionsCo2eq']);
        self::assertSame('Logística ACME', $rows[1]['provider']);
        self::assertSame('Empleados', $rows[2]['origin']);
        self::assertSame('', $rows[0]['property']);
        self::assertSame('', $rows[0]['manualCo2eq']);
        self::assertArrayHasKey('acquisitionYear', $rows[0]);
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
            new CompanyCarbonFootprintDetailExportFilter($footprint->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        ));
    }

    public function testDoesNotAuthorizeTheOwnerToExportAFootprintOfAnotherAccount(): void
    {
        $footprint = $this->completedFootprint('other-account');

        self::assertFalse($this->sut->isAuthorizedForFilter(
            new CompanyCarbonFootprintDetailExportFilter($footprint->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        ));
    }

    public function testDoesNotAuthorizeAFootprintThatIsNotCompleted(): void
    {
        $footprint = CompanyCarbonFootprintMother::withEmployeesAnswered('C-1', (string) CompanyCarbonFootprintId::generate());
        $this->companyCarbonFootprintRepository->add($footprint);

        self::assertFalse($this->sut->isAuthorizedForFilter(
            new CompanyCarbonFootprintDetailExportFilter($footprint->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        ));
    }

    public function testDoesNotAuthorizeAnInvalidFilter(): void
    {
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));

        self::assertFalse($this->sut->isAuthorizedForFilter(new \stdClass(), $owner));
        self::assertFalse($this->sut->isAuthorizedForFilter(new CompanyCarbonFootprintDetailExportFilter(), $owner));
        self::assertFalse($this->sut->isAuthorizedForFilter(new CompanyCarbonFootprintDetailExportFilter(CompanyCarbonFootprintId::generate()), $owner));
    }

    private function completedFootprint(string $accountId): CompanyCarbonFootprint
    {
        $footprint = CompanyCarbonFootprintMother::withEmployeesAnswered('C-1', (string) CompanyCarbonFootprintId::generate(), $accountId);
        $footprint->complete(new Emissions());
        $this->companyCarbonFootprintRepository->add($footprint);

        return $footprint;
    }

    /** @param Role[]|null $currentRoles */
    private function createSUT(AccountId $currentAccountId, ?array $currentRoles = null): CompanyCarbonFootprintDetailExportationType
    {
        $countedActivityReferences = CountedActivityReferencesMother::create();

        return new CompanyCarbonFootprintDetailExportationType(
            $this->companyCarbonFootprintRepository,
            new CompanyCarbonFootprintAuthorizer(new StubCurrentAccountIdProvider($currentAccountId), new StubCurrentRolesProvider($currentRoles)),
            new CarbonFootprintDetailRowsBuilder(
                $this->activityRepository,
                $countedActivityReferences,
                $this->itemNameResolver(),
                new DerivedSourceEmissionsCalculator($this->activityRepository, $countedActivityReferences),
                $this->providerRepository,
                PropertyRepositoryMother::inMemory(),
                new InMemoryUserRepository(),
            ),
            new CarbonFootprintDetailCellsFormatter(new FakeTranslator()),
            new StubCurrentRolesProvider($currentRoles),
            $this->requestStack,
        );
    }
}
