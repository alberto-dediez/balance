<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprint\Export\CarbonFootprintDetailRowsBuilder;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\Export\EventCarbonFootprintDetailExportFilter;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Infrastructure\DataExchange\CarbonFootprintDetailCellsFormatter;
use App\CarbonFootprint\Infrastructure\DataExchange\EventCarbonFootprintDetailExportationType;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\EventId;
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
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
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

class EventCarbonFootprintDetailExportationTypeTest extends TestCase
{
    use OrganizationalCollectorContextTrait;

    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;

    private InMemoryProviderRepository $providerRepository;

    private RequestStack $requestStack;

    private EventCarbonFootprintDetailExportationType $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->initActivities();
        $this->eventCarbonFootprintRepository = new InMemoryEventCarbonFootprintRepository();
        $this->providerRepository = ProviderRepositoryMother::inMemory();
        $this->requestStack = new RequestStack();

        $this->sut = $this->createSUT(new AccountId('account-id'));
    }

    public function testCreatesAnEmptyFilterWithoutRequest(): void
    {
        $filter = $this->sut->createFilter();

        self::assertInstanceOf(EventCarbonFootprintDetailExportFilter::class, $filter);
        self::assertNull($filter->eventCarbonFootprintId);
    }

    public function testPreselectsTheFootprintFromTheQueryString(): void
    {
        $eventCarbonFootprintId = (string) EventCarbonFootprintId::generate();

        $this->requestStack->push(Request::create(
            '/event_carbon_footprint_detail/export',
            parameters: ['eventCarbonFootprintId' => $eventCarbonFootprintId],
        ));

        $filter = $this->sut->createFilter();

        self::assertInstanceOf(EventCarbonFootprintDetailExportFilter::class, $filter);
        self::assertSame($eventCarbonFootprintId, (string) $filter->eventCarbonFootprintId);
    }

    public function testDehydratesAndHydratesTheFilter(): void
    {
        $eventCarbonFootprintId = EventCarbonFootprintId::generate();

        $criteria = $this->sut->dehydrateFilter(new EventCarbonFootprintDetailExportFilter($eventCarbonFootprintId));

        self::assertSame(['eventCarbonFootprintId' => (string) $eventCarbonFootprintId], $criteria);

        $filter = $this->sut->hydrateFilter($criteria);

        self::assertInstanceOf(EventCarbonFootprintDetailExportFilter::class, $filter);
        self::assertSame((string) $eventCarbonFootprintId, (string) $filter->eventCarbonFootprintId);
    }

    public function testDehydratesAnEmptyFilter(): void
    {
        $criteria = $this->sut->dehydrateFilter(new EventCarbonFootprintDetailExportFilter());

        self::assertSame(['eventCarbonFootprintId' => null], $criteria);

        $filter = $this->sut->hydrateFilter($criteria);

        self::assertInstanceOf(EventCarbonFootprintDetailExportFilter::class, $filter);
        self::assertNull($filter->eventCarbonFootprintId);
    }

    public function testYieldsNoRowsWithoutFootprint(): void
    {
        self::assertSame([], iterator_to_array($this->sut->rows(new EventCarbonFootprintDetailExportFilter(), [])));
        self::assertSame([], iterator_to_array($this->sut->rows(new EventCarbonFootprintDetailExportFilter(EventCarbonFootprintId::generate()), [])));
    }

    public function testYieldsTheTranslatedDetailOfTheFootprint(): void
    {
        $footprint = EventCarbonFootprintMother::fromEventIdAndAccount((string) EventId::generate(), 'account-id');
        $provider = ProviderMother::fromNameAndId('Logística ACME', (string) ProviderId::generate());
        $this->providerRepository->add($provider);
        $combustible = $this->save(CombustibleActivityMother::registered());
        $reported = $this->save(ReportedEmissionsActivityMother::registered(description: 'Reparto semanal'));
        $commuting = $this->save(ReportedEmissionsActivityMother::registered(description: 'Desplazamientos'));
        $footprint->addActivity($combustible->id(), new ActivityPlacement(Phase::Assembly, Source::FixedFacilities));
        $footprint->addActivity($reported->id(), new ActivityPlacement(Phase::Celebration, Source::SuppliersAndStaffMobility, providerId: $provider->id()));
        $footprint->addActivity($commuting->id(), new ActivityPlacement(Phase::Breakdown, Source::SuppliersAndStaffMobility, fromEmployees: true));
        $this->eventCarbonFootprintRepository->add($footprint);

        $rows = iterator_to_array($this->sut->rows(new EventCarbonFootprintDetailExportFilter($footprint->id()), []));

        self::assertCount(3, $rows);
        self::assertSame(
            array_map(static fn($column): string => $column->key, $this->sut->columnDefinitions()),
            array_keys($rows[0]),
        );
        self::assertSame('assembly', $rows[0]['phase']);
        self::assertSame('15.0', $rows[0]['emissionsCo2eq']);
        self::assertSame('Logística ACME', $rows[1]['provider']);
        self::assertSame('Empleados', $rows[2]['origin']);
        self::assertArrayNotHasKey('stage', $rows[0]);
        self::assertArrayNotHasKey('acquisitionYear', $rows[0]);
        self::assertArrayNotHasKey('property', $rows[0]);
        self::assertArrayNotHasKey('manualCo2eq', $rows[0]);
    }

    public function testTheMaterialityColumnsAreNotOffered(): void
    {
        $keys = array_map(static fn($column): string => $column->key, $this->sut->columnDefinitions());

        foreach (['materialityScorePercent', 'materialityThresholdPercent', 'materialityVerdict', 'materialityForcedByHighMagnitude', 'materialityOverride'] as $key) {
            self::assertNotContains($key, $keys);
        }
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
            new EventCarbonFootprintDetailExportFilter($footprint->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        ));
    }

    public function testDoesNotAuthorizeTheOwnerToExportAFootprintOfAnotherAccount(): void
    {
        $footprint = $this->completedFootprint('other-account');

        self::assertFalse($this->sut->isAuthorizedForFilter(
            new EventCarbonFootprintDetailExportFilter($footprint->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        ));
    }

    public function testDoesNotAuthorizeAFootprintThatIsNotCompleted(): void
    {
        $footprint = EventCarbonFootprintMother::fromEventIdAndAccount((string) EventId::generate(), 'account-id');
        $this->eventCarbonFootprintRepository->add($footprint);

        self::assertFalse($this->sut->isAuthorizedForFilter(
            new EventCarbonFootprintDetailExportFilter($footprint->id()),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        ));
    }

    public function testDoesNotAuthorizeAnInvalidFilter(): void
    {
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));

        self::assertFalse($this->sut->isAuthorizedForFilter(new \stdClass(), $owner));
        self::assertFalse($this->sut->isAuthorizedForFilter(new EventCarbonFootprintDetailExportFilter(), $owner));
        self::assertFalse($this->sut->isAuthorizedForFilter(new EventCarbonFootprintDetailExportFilter(EventCarbonFootprintId::generate()), $owner));
    }

    private function completedFootprint(string $accountId): EventCarbonFootprint
    {
        $footprint = EventCarbonFootprintMother::fromEventIdAndAccount((string) EventId::generate(), $accountId);
        $footprint->complete(new Emissions(), new Emissions());
        $this->eventCarbonFootprintRepository->add($footprint);

        return $footprint;
    }

    /** @param Role[]|null $currentRoles */
    private function createSUT(AccountId $currentAccountId, ?array $currentRoles = null): EventCarbonFootprintDetailExportationType
    {
        $countedActivityReferences = CountedActivityReferencesMother::create();

        return new EventCarbonFootprintDetailExportationType(
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(new StubCurrentAccountIdProvider($currentAccountId), new StubCurrentRolesProvider($currentRoles)),
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
