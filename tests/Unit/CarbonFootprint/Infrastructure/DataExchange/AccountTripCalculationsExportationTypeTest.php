<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\DataExchange;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectRepository;
use App\CarbonFootprint\Application\Contribution\OriginCompensationsCalculator;
use App\CarbonFootprint\Application\TripCalculation\Export\AccountTripCalculationsExportFilter;
use App\CarbonFootprint\Application\TripCalculationLink\TripCalculationLinkAuthorizer;
use App\CarbonFootprint\Domain\Model\Trip\TripWaypoint;
use App\CarbonFootprint\Infrastructure\DataExchange\AccountTripCalculationsExportationType;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationLinkRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationRepository;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AccountTripCalculationsExportationTypeTest extends TestCase
{
    private InMemoryTripCalculationRepository $tripCalculationRepository;

    private InMemoryTripCalculationLinkRepository $tripCalculationLinkRepository;

    private InMemoryProjectContributionRepository $projectContributionRepository;

    private InMemoryProjectRepository $projectRepository;

    private RequestStack $requestStack;

    private AccountTripCalculationsExportationType $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->tripCalculationRepository = new InMemoryTripCalculationRepository();
        $this->tripCalculationLinkRepository = new InMemoryTripCalculationLinkRepository();
        $this->projectContributionRepository = new InMemoryProjectContributionRepository();
        $this->projectRepository = new InMemoryProjectRepository();
        $this->requestStack = new RequestStack();

        $this->sut = $this->sutWithCurrentAccount(new AccountId('account-id'));
    }

    public function testExportsTheDestinationColumnAfterTheOrigin(): void
    {
        $keys = array_map(static fn($column): string => $column->key, $this->sut->columnDefinitions());

        self::assertSame('account_trip_calculations', $this->sut->code());
        self::assertSame(['registeredAt', 'origin', 'destination'], array_slice($keys, 0, 3));
    }

    public function testTheAccountOfTheFilterIsTheCurrentAccountEvenIfTheQueryAsksForAnother(): void
    {
        $this->requestStack->push(Request::create(
            '/account_trip_calculations/export',
            parameters: ['accountId' => 'another-account-id', 'from' => '2026-01-01', 'to' => '2026-01-31'],
        ));

        $filter = $this->sut->createFilter();

        self::assertInstanceOf(AccountTripCalculationsExportFilter::class, $filter);
        self::assertSame('account-id', (string) $filter->accountId);
        self::assertSame('2026-01-01', $filter->from?->format('Y-m-d'));
        self::assertSame('2026-01-31', $filter->to?->format('Y-m-d'));
    }

    public function testDehydratesAndHydratesTheFilter(): void
    {
        $criteria = $this->sut->dehydrateFilter(new AccountTripCalculationsExportFilter(
            accountId: new AccountId('account-id'),
            from: new \DateTimeImmutable('2026-01-01'),
            to: new \DateTimeImmutable('2026-01-31'),
        ));

        self::assertSame(['accountId' => 'account-id', 'from' => '2026-01-01', 'to' => '2026-01-31'], $criteria);

        $filter = $this->sut->hydrateFilter($criteria);

        self::assertInstanceOf(AccountTripCalculationsExportFilter::class, $filter);
        self::assertSame('account-id', (string) $filter->accountId);
        self::assertSame('2026-01-31', $filter->to?->format('Y-m-d'));
    }

    public function testOnlyExportsTheCalculationsOfTheAccountCalculatorOfTheFilter(): void
    {
        $accountLink = TripCalculationLinkMother::forAccount('account-id');
        $propertyLink = TripCalculationLinkMother::fromPropertyIdAndAccount((string) PropertyId::generate(), 'account-id');
        $foreignLink = TripCalculationLinkMother::forAccount('another-account-id');
        $this->tripCalculationLinkRepository->add($accountLink);
        $this->tripCalculationLinkRepository->add($propertyLink);
        $this->tripCalculationLinkRepository->add($foreignLink);

        $this->tripCalculationRepository->add(TripCalculationMother::withDestination(
            (string) $accountLink->id(),
            new TripWaypoint(label: 'Valencia, España'),
            new TripWaypoint(label: 'Sevilla, España'),
        ));
        $this->tripCalculationRepository->add(TripCalculationMother::fromLinkId((string) $propertyLink->id()));
        $this->tripCalculationRepository->add(TripCalculationMother::fromLinkId((string) $foreignLink->id()));

        $rows = iterator_to_array($this->sut->rows(new AccountTripCalculationsExportFilter(accountId: new AccountId('account-id')), []));

        self::assertCount(1, $rows);
        self::assertSame('Sevilla, España', $rows[0]['origin']);
        self::assertSame('Valencia, España', $rows[0]['destination']);
    }

    public function testYieldsNoRowsWithoutCalculator(): void
    {
        $rows = $this->sut->rows(new AccountTripCalculationsExportFilter(accountId: new AccountId('account-id')), []);

        self::assertSame([], iterator_to_array($rows));
    }

    public function testReportsTheCompensationsStartedFromTheAccountCalculator(): void
    {
        $link = TripCalculationLinkMother::forAccount('account-id');
        $this->tripCalculationLinkRepository->add($link);

        $tripCalculation = TripCalculationMother::registeredAt((string) $link->id(), '2026-01-10 09:30:00');
        $this->tripCalculationRepository->add($tripCalculation);

        $projectId = ProjectId::generate();
        $this->projectRepository->add(ProjectMother::fromNameAndId('Bosque de La Rioja', (string) $projectId));
        $this->projectContributionRepository->add(ProjectContributionMother::paid(
            new AccountId('account-id'),
            $projectId,
            tonnes: 0.25,
            originId: (string) $tripCalculation->id(),
            origin: ProjectContributionOrigin::TripCalculation,
        ));

        $rows = iterator_to_array($this->sut->rows(new AccountTripCalculationsExportFilter(accountId: new AccountId('account-id')), []));

        self::assertSame('Sí', $rows[0]['compensated']);
        self::assertSame('Bosque de La Rioja', $rows[0]['project']);
        self::assertSame(250.0, $rows[0]['compensatedEmissions']);
    }

    public function testOnlyOwnersOfTheCurrentAccountAreAuthorized(): void
    {
        self::assertTrue($this->sut->isAuthorized(
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        ));

        self::assertFalse($this->sut->isAuthorized(UserMother::registeredUser()));
    }

    public function testAuthorizesTheFilterOfTheCurrentAccount(): void
    {
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));

        self::assertTrue($this->sut->isAuthorizedForFilter(new AccountTripCalculationsExportFilter(accountId: new AccountId('account-id')), $owner));
        self::assertFalse($this->sut->isAuthorizedForFilter(new AccountTripCalculationsExportFilter(accountId: new AccountId('another-account-id')), $owner));
        self::assertFalse($this->sut->isAuthorizedForFilter(new AccountTripCalculationsExportFilter(), $owner));
        self::assertFalse($this->sut->isAuthorizedForFilter(new \stdClass(), $owner));
    }

    private function sutWithCurrentAccount(AccountId $currentAccountId): AccountTripCalculationsExportationType
    {
        $currentAccountIdProvider = new StubCurrentAccountIdProvider($currentAccountId);

        return new AccountTripCalculationsExportationType(
            $this->tripCalculationRepository,
            $this->tripCalculationLinkRepository,
            new OriginCompensationsCalculator($this->projectContributionRepository, $this->projectRepository),
            new TripCalculationLinkAuthorizer(new InMemoryPropertyRepository(), new PropertyAuthorizer(), $currentAccountIdProvider),
            new FakeTranslator(),
            $currentAccountIdProvider,
            $this->requestStack,
        );
    }
}
