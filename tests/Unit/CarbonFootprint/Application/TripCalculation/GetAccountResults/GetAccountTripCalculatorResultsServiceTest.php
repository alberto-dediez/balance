<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\TripCalculation\GetAccountResults;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\CarbonFootprint\Application\TripCalculation\GetAccountResults\GetAccountTripCalculatorResultsRequest;
use App\CarbonFootprint\Application\TripCalculation\GetAccountResults\GetAccountTripCalculatorResultsService;
use App\CarbonFootprint\Application\TripCalculationLink\TripCalculationLinkAuthorizer;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Trip\TripWaypoint;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationLinkRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationRepository;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Geo\GeoPoint;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\CarbonFootprint\Application\TripCalculation\GetResults\TripCalculationResultsBuilderMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

class GetAccountTripCalculatorResultsServiceTest extends TestCase
{
    private InMemoryTripCalculationLinkRepository $tripCalculationLinkRepository;

    private InMemoryTripCalculationRepository $tripCalculationRepository;

    private InMemoryProjectContributionRepository $projectContributionRepository;

    private Account $account;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->tripCalculationLinkRepository = new InMemoryTripCalculationLinkRepository();
        $this->tripCalculationRepository = new InMemoryTripCalculationRepository();
        $this->projectContributionRepository = new InMemoryProjectContributionRepository();
        $this->account = AccountMother::fromName('Viajes Sostenibles');
    }

    public function testReturnsEmptyResultsWhenTheAccountHasNoCalculatorYet(): void
    {
        $response = ($this->createSUT())(new GetAccountTripCalculatorResultsRequest(), $this->owner());

        $this->assertNull($response->tripCalculationLink);
        $this->assertNull($response->property);
        $this->assertSame('Viajes Sostenibles', $response->ownerName);
        $this->assertSame(0, $response->aggregation->calculations);
    }

    public function testAggregatesTheCalculationsWithTheirDestinations(): void
    {
        $link = $this->link();
        $madrid = new TripWaypoint(label: 'Madrid', point: new GeoPoint(40.4168, -3.7038));
        $valencia = new TripWaypoint(reference: 'valencia', label: 'Valencia', point: new GeoPoint(39.4699, -0.3763));
        $paris = new TripWaypoint(reference: 'paris', label: 'París', point: new GeoPoint(48.8566, 2.3522));

        $this->tripCalculationRepository->add(TripCalculationMother::withDestination((string) $link->id(), $valencia, $madrid, people: 2));
        $this->tripCalculationRepository->add(TripCalculationMother::withDestination((string) $link->id(), $valencia, $madrid, people: 1));
        $this->tripCalculationRepository->add(TripCalculationMother::withDestination((string) $link->id(), $paris, $madrid));
        $this->tripCalculationRepository->add(TripCalculationMother::fromLinkId((string) $link->id()));

        $response = ($this->createSUT())(new GetAccountTripCalculatorResultsRequest(), $this->owner());

        $this->assertSame(4, $response->aggregation->calculations);
        $this->assertSame(['Valencia' => 2, 'París' => 1], $response->topDestinations);
        $this->assertCount(2, $response->originDestinationFlows);
        $this->assertSame('Valencia', $response->originDestinationFlows[0]->destinationLabel);
        $this->assertSame(3, $response->originDestinationFlows[0]->travellers);
        $this->assertSame(1, $response->undestinedCalculations);
        $this->assertNull($response->destination);
    }

    public function testOnlyCountsTheContributionsStartedFromTheAccountCalculator(): void
    {
        $link = $this->link();
        $calculationId = (string) TripCalculationId::generate();
        $this->tripCalculationRepository->add(TripCalculationMother::singleLeg(
            tripCalculationLinkId: (string) $link->id(),
            mobilityId: (string) MobilityId::generate(),
            distanceKm: 100.0,
            mobilityEmissions: new Emissions(co2eq: '1000'),
            id: $calculationId,
        ));

        $this->projectContributionRepository->add(ProjectContributionMother::paid(tonnes: 1.0, originId: $calculationId, origin: ProjectContributionOrigin::TripCalculation));
        $this->projectContributionRepository->add(ProjectContributionMother::paid(tonnes: 1.0, originId: $calculationId, origin: ProjectContributionOrigin::PropertyTripCalculation));

        $response = ($this->createSUT())(new GetAccountTripCalculatorResultsRequest(), $this->owner());

        $this->assertSame(1, $response->contributions->aggregation->contributions);
        $this->assertSame(1, $response->contributions->aggregation->paid);
    }

    public function testDeniesAnOwnerOfAnotherAccount(): void
    {
        $this->link();

        $this->expectException(UnauthorizedActionException::class);

        ($this->createSUT())(
            new GetAccountTripCalculatorResultsRequest(),
            UserMother::fromUsernameAndRoleAndAccount('intruder', Role::Owner, AccountId::generate()),
        );
    }

    private function link(): TripCalculationLink
    {
        $link = TripCalculationLinkMother::forAccount((string) $this->account->id());
        $this->tripCalculationLinkRepository->add($link);

        return $link;
    }

    private function owner(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $this->account->id());
    }

    private function createSUT(): GetAccountTripCalculatorResultsService
    {
        return new GetAccountTripCalculatorResultsService(
            new StubCurrentAccountIdProvider($this->account->id()),
            new InMemoryAccountRepository([(string) $this->account->id() => $this->account]),
            $this->tripCalculationLinkRepository,
            new TripCalculationLinkAuthorizer(new InMemoryPropertyRepository(), new PropertyAuthorizer()),
            TripCalculationResultsBuilderMother::create($this->tripCalculationRepository, $this->projectContributionRepository),
        );
    }
}
