<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\DataExchange;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectRepository;
use App\CarbonFootprint\Application\Contribution\OriginCompensationsCalculator;
use App\CarbonFootprint\Application\TripCalculation\Export\PropertyTripCalculationsExportFilter;
use App\CarbonFootprint\Infrastructure\DataExchange\PropertyTripCalculationsExportationType;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationLinkRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationRepository;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PropertyTripCalculationsExportationTypeTest extends TestCase
{
    private InMemoryTripCalculationRepository $tripCalculationRepository;

    private InMemoryTripCalculationLinkRepository $tripCalculationLinkRepository;

    private InMemoryProjectContributionRepository $projectContributionRepository;

    private InMemoryProjectRepository $projectRepository;

    private InMemoryPropertyRepository $propertyRepository;

    private RequestStack $requestStack;

    private PropertyTripCalculationsExportationType $sut;

    protected function setUp(): void
    {
        $this->tripCalculationRepository = new InMemoryTripCalculationRepository();
        $this->tripCalculationLinkRepository = new InMemoryTripCalculationLinkRepository();
        $this->projectContributionRepository = new InMemoryProjectContributionRepository();
        $this->projectRepository = new InMemoryProjectRepository();
        $this->propertyRepository = new InMemoryPropertyRepository();
        $this->requestStack = new RequestStack();

        $this->sut = $this->sutWithCurrentAccount(new AccountId('account-id'));
    }

    public function testCreatesAnEmptyFilterWithoutRequest(): void
    {
        $filter = $this->sut->createFilter();

        self::assertInstanceOf(PropertyTripCalculationsExportFilter::class, $filter);
        self::assertNull($filter->propertyId);
        self::assertNull($filter->from);
        self::assertNull($filter->to);
    }

    public function testPreselectsPropertyAndDateRangeFromTheQueryString(): void
    {
        $propertyId = (string) PropertyId::generate();

        $this->requestStack->push(Request::create(
            '/property_trip_calculations/export',
            parameters: ['propertyId' => $propertyId, 'from' => '2026-01-01', 'to' => '2026-01-31'],
        ));

        $filter = $this->sut->createFilter();

        self::assertInstanceOf(PropertyTripCalculationsExportFilter::class, $filter);
        self::assertSame($propertyId, (string) $filter->propertyId);
        self::assertSame('2026-01-01', $filter->from?->format('Y-m-d'));
        self::assertSame('2026-01-31', $filter->to?->format('Y-m-d'));
    }

    public function testIgnoresEmptyAndInvalidQueryStringValues(): void
    {
        $this->requestStack->push(Request::create(
            '/property_trip_calculations/export',
            parameters: ['propertyId' => '', 'from' => 'not-a-date', 'to' => ' '],
        ));

        $filter = $this->sut->createFilter();

        self::assertInstanceOf(PropertyTripCalculationsExportFilter::class, $filter);
        self::assertNull($filter->propertyId);
        self::assertNull($filter->from);
        self::assertNull($filter->to);
    }

    public function testDehydratesAndHydratesTheFilter(): void
    {
        $propertyId = PropertyId::generate();

        $criteria = $this->sut->dehydrateFilter(new PropertyTripCalculationsExportFilter(
            propertyId: $propertyId,
            from: new \DateTimeImmutable('2026-01-01'),
            to: new \DateTimeImmutable('2026-01-31'),
        ));

        self::assertSame(
            ['propertyId' => (string) $propertyId, 'from' => '2026-01-01', 'to' => '2026-01-31'],
            $criteria,
        );

        $filter = $this->sut->hydrateFilter($criteria);

        self::assertInstanceOf(PropertyTripCalculationsExportFilter::class, $filter);
        self::assertSame((string) $propertyId, (string) $filter->propertyId);
        self::assertSame('2026-01-01', $filter->from?->format('Y-m-d'));
        self::assertSame('2026-01-31', $filter->to?->format('Y-m-d'));
    }

    public function testDehydratesAnEmptyFilterWithoutNulls(): void
    {
        $criteria = $this->sut->dehydrateFilter(new PropertyTripCalculationsExportFilter());

        self::assertSame(['propertyId' => null, 'from' => null, 'to' => null], $criteria);

        $filter = $this->sut->hydrateFilter($criteria);

        self::assertInstanceOf(PropertyTripCalculationsExportFilter::class, $filter);
        self::assertNull($filter->propertyId);
    }

    public function testYieldsNoRowsWhenThePropertyHasNoLink(): void
    {
        $rows = $this->sut->rows(
            new PropertyTripCalculationsExportFilter(propertyId: PropertyId::generate()),
            [],
        );

        self::assertSame([], iterator_to_array($rows));
    }

    public function testYieldsTheCalculationsOfThePropertyInChronologicalOrder(): void
    {
        $propertyId = PropertyId::generate();
        $link = TripCalculationLinkMother::fromPropertyId((string) $propertyId);
        $this->tripCalculationLinkRepository->add($link);

        $this->tripCalculationRepository->add(
            TripCalculationMother::registeredAt((string) $link->id(), '2026-01-20 10:00:00'),
        );
        $this->tripCalculationRepository->add(
            TripCalculationMother::registeredAt((string) $link->id(), '2026-01-10 09:30:00'),
        );

        $rows = iterator_to_array($this->sut->rows(
            new PropertyTripCalculationsExportFilter(propertyId: $propertyId),
            [],
        ));

        self::assertCount(2, $rows);
        self::assertSame('10/01/2026 09:30', $rows[0]['registeredAt']);
        self::assertSame('20/01/2026 10:00', $rows[1]['registeredAt']);
    }

    public function testIncludesTheCalculationsOfTheLastDayOfTheRange(): void
    {
        $propertyId = PropertyId::generate();
        $link = TripCalculationLinkMother::fromPropertyId((string) $propertyId);
        $this->tripCalculationLinkRepository->add($link);

        $this->tripCalculationRepository->add(
            TripCalculationMother::registeredAt((string) $link->id(), '2026-01-31 18:45:00'),
        );

        $rows = iterator_to_array($this->sut->rows(
            new PropertyTripCalculationsExportFilter(
                propertyId: $propertyId,
                from: new \DateTimeImmutable('2026-01-01'),
                to: new \DateTimeImmutable('2026-01-31'),
            ),
            [],
        ));

        self::assertCount(1, $rows);
        self::assertSame('31/01/2026 18:45', $rows[0]['registeredAt']);
    }

    public function testReportsTheCompensationOfEachCalculation(): void
    {
        $propertyId = PropertyId::generate();
        $link = TripCalculationLinkMother::fromPropertyId((string) $propertyId);
        $this->tripCalculationLinkRepository->add($link);

        $compensated = TripCalculationMother::registeredAt((string) $link->id(), '2026-01-10 09:30:00');
        $notCompensated = TripCalculationMother::registeredAt((string) $link->id(), '2026-01-20 10:00:00');
        $this->tripCalculationRepository->add($compensated);
        $this->tripCalculationRepository->add($notCompensated);

        $projectId = ProjectId::generate();
        $this->projectRepository->add(ProjectMother::fromNameAndId('Bosque de La Rioja', (string) $projectId));

        $this->projectContributionRepository->add(ProjectContributionMother::paid(
            new AccountId('account-id'),
            $projectId,
            tonnes: 0.25,
            originId: (string) $compensated->id(),
        ));

        $rows = iterator_to_array($this->sut->rows(
            new PropertyTripCalculationsExportFilter(propertyId: $propertyId),
            [],
        ));

        self::assertSame('Sí', $rows[0]['compensated']);
        self::assertSame('Bosque de La Rioja', $rows[0]['project']);
        self::assertSame(6.05, $rows[0]['contributionAmount']);
        self::assertSame(250.0, $rows[0]['compensatedEmissions']);

        self::assertSame('No', $rows[1]['compensated']);
        self::assertSame('', $rows[1]['project']);
        self::assertSame('', $rows[1]['contributionAmount']);
        self::assertSame('', $rows[1]['compensatedEmissions']);
    }

    public function testDoesNotReportPendingContributionsAsCompensated(): void
    {
        $propertyId = PropertyId::generate();
        $link = TripCalculationLinkMother::fromPropertyId((string) $propertyId);
        $this->tripCalculationLinkRepository->add($link);

        $tripCalculation = TripCalculationMother::registeredAt((string) $link->id(), '2026-01-10 09:30:00');
        $this->tripCalculationRepository->add($tripCalculation);

        $this->projectContributionRepository->add(ProjectContributionMother::pending(
            new AccountId('account-id'),
            ProjectId::generate(),
            originId: (string) $tripCalculation->id(),
        ));

        $rows = iterator_to_array($this->sut->rows(
            new PropertyTripCalculationsExportFilter(propertyId: $propertyId),
            [],
        ));

        self::assertSame('No', $rows[0]['compensated']);
        self::assertSame('', $rows[0]['compensatedEmissions']);
    }

    public function testOnlyOwnersOfTheCurrentAccountAreAuthorized(): void
    {
        self::assertTrue($this->sut->isAuthorized(
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        ));

        self::assertFalse($this->sut->isAuthorized(UserMother::registeredUser()));
    }

    public function testAuthorizesTheOwnerToExportAPropertyOfTheCurrentAccount(): void
    {
        $propertyId = PropertyId::generate();
        $this->propertyRepository->add(PropertyMother::from($propertyId, accountId: new AccountId('account-id')));

        self::assertTrue($this->sut->isAuthorizedForFilter(
            new PropertyTripCalculationsExportFilter(propertyId: $propertyId),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        ));
    }

    public function testDoesNotAuthorizeTheOwnerToExportAPropertyOfAnotherAccount(): void
    {
        $propertyId = PropertyId::generate();
        $this->propertyRepository->add(PropertyMother::from($propertyId, accountId: new AccountId('other-account')));

        self::assertFalse($this->sut->isAuthorizedForFilter(
            new PropertyTripCalculationsExportFilter(propertyId: $propertyId),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id')),
        ));
    }

    public function testAuthorizesAnImpersonatedAccountProperty(): void
    {
        $sut = $this->sutWithCurrentAccount(new AccountId('impersonated'));
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));

        $impersonatedPropertyId = PropertyId::generate();
        $this->propertyRepository->add(PropertyMother::from($impersonatedPropertyId, accountId: new AccountId('impersonated')));

        $ownPropertyId = PropertyId::generate();
        $this->propertyRepository->add(PropertyMother::from($ownPropertyId, accountId: new AccountId('account-id')));

        self::assertTrue($sut->isAuthorizedForFilter(
            new PropertyTripCalculationsExportFilter(propertyId: $impersonatedPropertyId),
            $owner,
        ));

        self::assertFalse($sut->isAuthorizedForFilter(
            new PropertyTripCalculationsExportFilter(propertyId: $ownPropertyId),
            $owner,
        ));
    }

    public function testAuthorizesAdminsToExportAnyProperty(): void
    {
        $propertyId = PropertyId::generate();
        $this->propertyRepository->add(PropertyMother::from($propertyId, accountId: new AccountId('other-account')));

        self::assertTrue($this->sut->isAuthorizedForFilter(
            new PropertyTripCalculationsExportFilter(propertyId: $propertyId),
            UserMother::adminUser(),
        ));
    }

    public function testDoesNotAuthorizeAFilterWithoutProperty(): void
    {
        self::assertFalse($this->sut->isAuthorizedForFilter(
            new PropertyTripCalculationsExportFilter(),
            UserMother::adminUser(),
        ));
    }

    public function testDoesNotAuthorizeAnUnknownProperty(): void
    {
        self::assertFalse($this->sut->isAuthorizedForFilter(
            new PropertyTripCalculationsExportFilter(propertyId: PropertyId::generate()),
            UserMother::adminUser(),
        ));
    }

    public function testDoesNotAuthorizeAnotherFilterClass(): void
    {
        self::assertFalse($this->sut->isAuthorizedForFilter(new \stdClass(), UserMother::adminUser()));
    }

    private function sutWithCurrentAccount(AccountId $currentAccountId): PropertyTripCalculationsExportationType
    {
        return new PropertyTripCalculationsExportationType(
            $this->tripCalculationRepository,
            $this->tripCalculationLinkRepository,
            new OriginCompensationsCalculator($this->projectContributionRepository, $this->projectRepository),
            new PropertyAuthorizer(new StubCurrentAccountIdProvider($currentAccountId)),
            $this->propertyRepository,
            new FakeTranslator(),
            new StubCurrentAccountIdProvider($currentAccountId),
            $this->requestStack,
        );
    }
}
