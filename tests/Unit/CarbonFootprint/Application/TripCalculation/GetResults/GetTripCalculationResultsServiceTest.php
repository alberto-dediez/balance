<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\TripCalculation\GetResults;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionsAggregator;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository;
use App\CarbonFootprint\Application\Activity\ActivityEmissionsResolver;
use App\CarbonFootprint\Application\Activity\EmissionFactorSelectorRegistry;
use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Application\Activity\MobilityEmissionFactorSelector;
use App\CarbonFootprint\Application\Trip\MobilityBreakdownCalculator;
use App\CarbonFootprint\Application\TripCalculation\GetResults\GetTripCalculationResultsRequest;
use App\CarbonFootprint\Application\TripCalculation\GetResults\GetTripCalculationResultsService;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Trip\TripLegsAccumulator;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculation;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationId;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationsAggregator;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmissionFactorGroupRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMobilityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationLinkRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationRepository;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GetTripCalculationResultsServiceTest extends TestCase
{
    private InMemoryPropertyRepository $propertyRepository;

    private InMemoryTripCalculationLinkRepository $tripCalculationLinkRepository;

    private InMemoryTripCalculationRepository $tripCalculationRepository;

    private InMemoryMobilityRepository $mobilityRepository;

    private InMemoryProjectContributionRepository $projectContributionRepository;

    private GetTripCalculationResultsService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->propertyRepository = new InMemoryPropertyRepository();
        $this->tripCalculationLinkRepository = new InMemoryTripCalculationLinkRepository();
        $this->tripCalculationRepository = new InMemoryTripCalculationRepository();
        $this->mobilityRepository = new InMemoryMobilityRepository();
        $this->projectContributionRepository = new InMemoryProjectContributionRepository();

        $this->sut = new GetTripCalculationResultsService(
            $this->propertyRepository,
            new PropertyAuthorizer(),
            $this->tripCalculationLinkRepository,
            $this->tripCalculationRepository,
            new TripCalculationsAggregator(new TripLegsAccumulator()),
            new MobilityBreakdownCalculator(
                $this->mobilityRepository,
                new ActivityEmissionsResolver(
                    new EmissionFactorSelectorRegistry([new MobilityEmissionFactorSelector($this->mobilityRepository)]),
                    new EmissionsCalculator(),
                ),
                new InMemoryEmissionFactorGroupRepository(),
            ),
            $this->projectContributionRepository,
            new ProjectContributionsAggregator(),
        );
    }

    public function testReturnsAnEmptyAggregationWhenThePropertyHasNoLinkYet(): void
    {
        $property = $this->property();

        $response = ($this->sut)(new GetTripCalculationResultsRequest($property->id()), $this->ownerUser());

        $this->assertNull($response->tripCalculationLink);
        $this->assertSame(0, $response->aggregation->calculations);
        $this->assertSame([], $response->mobilityBreakdown);
    }

    public function testAggregatesTheCalculationsOfTheProperty(): void
    {
        $property = $this->property();
        $link = TripCalculationLinkMother::fromPropertyId((string) $property->id());
        $this->tripCalculationLinkRepository->add($link);

        $mobilityId = (string) MobilityId::generate();

        $this->tripCalculationRepository->add(TripCalculationMother::singleLeg(
            tripCalculationLinkId: (string) $link->id(),
            mobilityId: $mobilityId,
            distanceKm: 100.0,
            nights: 2,
            mobilityEmissions: new Emissions(co2eq: '10'),
        ));
        $this->tripCalculationRepository->add(TripCalculationMother::singleLeg(
            tripCalculationLinkId: (string) $link->id(),
            mobilityId: $mobilityId,
            distanceKm: 50.0,
            nights: 1,
            mobilityEmissions: new Emissions(co2eq: '5'),
        ));

        $response = ($this->sut)(new GetTripCalculationResultsRequest($property->id()), $this->ownerUser());

        $this->assertSame(2, $response->aggregation->calculations);
        $this->assertSame(3.0, $response->aggregation->nights);
        $this->assertEqualsWithDelta(150.0, $response->aggregation->kmByMobilityId[$mobilityId], 0.001);
        $this->assertEqualsWithDelta(15.0, (float) $response->aggregation->totalEmissions->co2eq(), 0.001);
    }

    public function testRestrictsTheAggregationToThePeriod(): void
    {
        $property = $this->property();
        $link = TripCalculationLinkMother::fromPropertyId((string) $property->id());
        $this->tripCalculationLinkRepository->add($link);

        $this->tripCalculationRepository->add(TripCalculationMother::registeredAt((string) $link->id(), '2026-01-15 10:00:00'));
        $this->tripCalculationRepository->add(TripCalculationMother::registeredAt((string) $link->id(), '2026-02-15 10:00:00'));

        $response = ($this->sut)(
            new GetTripCalculationResultsRequest(
                $property->id(),
                new \DateTimeImmutable('2026-02-01 00:00:00'),
                new \DateTimeImmutable('2026-02-28 23:59:59'),
            ),
            $this->ownerUser(),
        );

        $this->assertSame(1, $response->aggregation->calculations);
    }

    public function testAggregatesTheContributionsStartedFromTheCalculationsOfThePeriod(): void
    {
        $property = $this->property();
        $link = TripCalculationLinkMother::fromPropertyId((string) $property->id());
        $this->tripCalculationLinkRepository->add($link);

        $paidCalculationId = (string) TripCalculationId::generate();
        $pendingCalculationId = (string) TripCalculationId::generate();

        $this->tripCalculationRepository->add($this->calculation($link, $paidCalculationId, new Emissions(co2eq: '1500')));
        $this->tripCalculationRepository->add($this->calculation($link, $pendingCalculationId, new Emissions(co2eq: '500')));
        $this->tripCalculationRepository->add($this->calculation($link, (string) TripCalculationId::generate(), new Emissions(co2eq: '1000')));

        $this->projectContributionRepository->add(ProjectContributionMother::paid(tonnes: 1.5, originId: $paidCalculationId));
        $this->projectContributionRepository->add(ProjectContributionMother::pending(originId: $pendingCalculationId));
        $this->projectContributionRepository->add(ProjectContributionMother::paid(tonnes: 2.0));

        $response = ($this->sut)(new GetTripCalculationResultsRequest($property->id()), $this->ownerUser());

        $this->assertSame(2, $response->contributions->aggregation->contributions);
        $this->assertSame(1, $response->contributions->aggregation->paid);
        $this->assertSame(1, $response->contributions->aggregation->pending);
        $this->assertEqualsWithDelta(1.5, $response->contributions->aggregation->paidTonnes, 0.001);
        $this->assertEqualsWithDelta(36.3, $response->contributions->aggregation->paidTotalAmount, 0.001);
        $this->assertEqualsWithDelta(36.3, $response->contributions->aggregation->averagePaidAmount, 0.001);

        $this->assertEqualsWithDelta(66.666, $response->contributions->startedRate, 0.01);
        $this->assertEqualsWithDelta(33.333, $response->contributions->paidRate, 0.01);
        $this->assertEqualsWithDelta(50.0, $response->contributions->completionRate, 0.01);
        $this->assertEqualsWithDelta(50.0, $response->contributions->compensatedEmissionsRate, 0.01);
    }

    public function testReturnsEmptyContributionStatisticsWhenThereAreNoCalculations(): void
    {
        $property = $this->property();
        $this->tripCalculationLinkRepository->add(TripCalculationLinkMother::fromPropertyId((string) $property->id()));

        $this->projectContributionRepository->add(ProjectContributionMother::paid());

        $response = ($this->sut)(new GetTripCalculationResultsRequest($property->id()), $this->ownerUser());

        $this->assertSame(0, $response->contributions->aggregation->contributions);
        $this->assertSame(0.0, $response->contributions->startedRate);
        $this->assertSame(0.0, $response->contributions->paidRate);
        $this->assertSame(0.0, $response->contributions->completionRate);
        $this->assertSame(0.0, $response->contributions->compensatedEmissionsRate);
    }

    public function testDeniesAnOwnerOfAnotherAccount(): void
    {
        $property = $this->property();

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new GetTripCalculationResultsRequest($property->id()),
            UserMother::fromUsernameAndRoleAndAccount('other', Role::Owner, new AccountId('other-account-id')),
        );
    }

    public function testThrowsWhenThePropertyDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new GetTripCalculationResultsRequest(PropertyId::generate()), $this->ownerUser());
    }

    private function calculation(TripCalculationLink $link, string $id, Emissions $mobilityEmissions): TripCalculation
    {
        return TripCalculationMother::singleLeg(
            tripCalculationLinkId: (string) $link->id(),
            mobilityId: (string) MobilityId::generate(),
            distanceKm: 100.0,
            mobilityEmissions: $mobilityEmissions,
            id: $id,
        );
    }

    private function property(): Property
    {
        $property = PropertyMother::fromNameAndAccountAndId('Hotel', 'account-id', (string) PropertyId::generate());
        $this->propertyRepository->add($property);

        return $property;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
