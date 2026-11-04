<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Trip;

use App\CarbonFootprint\Application\Trip\PropertyLocator;
use App\CarbonFootprint\Application\Trip\TripDestinationCandidates;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationMode;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkMother;
use App\Tests\Unit\Property\Application\DistributorAssignment\DistributedPropertiesPolicyMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Application\Account\ConsultantSupervisionPolicyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use PHPUnit\Framework\TestCase;

class TripDestinationCandidatesTest extends TestCase
{
    private InMemoryPropertyRepository $propertyRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->propertyRepository = new InMemoryPropertyRepository();
    }

    public function testAnAccountOnlyGetsItsLocatedProperties(): void
    {
        $account = AccountMother::fromName('Agencia');
        $located = $this->locatedProperty('Hotel Mar', $account->id());
        $this->propertyRepository->add(PropertyMother::from(PropertyId::generate(), 'Sin coordenadas', $account->id()));
        $this->locatedProperty('Hotel ajeno', AccountId::generate());

        $candidates = $this->createSUT([$account])->candidatesFor($account->id());

        $this->assertSame([(string) $located->id()], $this->idsOf($candidates));
    }

    public function testAnActiveConsultantAlsoGetsThePropertiesOfItsManagedAccounts(): void
    {
        $consultant = AccountMother::consultantAccount();
        $managed = AccountMother::fromNameWithConsultant('Hotel asociado', $consultant->id());
        $own = $this->locatedProperty('B propia', $consultant->id());
        $managedProperty = $this->locatedProperty('A gestionada', $managed->id());
        $this->locatedProperty('C ajena', AccountId::generate());

        $candidates = $this->createSUT([$consultant, $managed])->candidatesFor($consultant->id());

        $this->assertSame([(string) $managedProperty->id(), (string) $own->id()], $this->idsOf($candidates));
    }

    public function testAnInactiveConsultantOnlyGetsItsOwnProperties(): void
    {
        $consultant = AccountMother::consultantAccount();
        $consultant->changeStatus(false);
        $managed = AccountMother::fromNameWithConsultant('Hotel asociado', $consultant->id());
        $own = $this->locatedProperty('Propia', $consultant->id());
        $this->locatedProperty('Gestionada', $managed->id());

        $candidates = $this->createSUT([$consultant, $managed])->candidatesFor($consultant->id());

        $this->assertSame([(string) $own->id()], $this->idsOf($candidates));
    }

    public function testIncludesDistributedPropertiesByAccountAndByProperty(): void
    {
        $distributor = AccountMother::distributorAccount();
        $hotel = AccountMother::fromName('Hotel A');
        $other = AccountMother::fromName('Hotel B');
        $own = $this->locatedProperty('C propia', $distributor->id());
        $byAccount = $this->locatedProperty('A por cuenta', $hotel->id());
        $byProperty = $this->locatedProperty('B por propiedad', $other->id());
        $this->locatedProperty('D no adherida', $other->id());

        $candidates = $this->createSUT([$distributor, $hotel, $other], [
            DistributorAssignmentMother::forAccount($distributor->id(), $hotel->id()),
            DistributorAssignmentMother::forProperty($distributor->id(), $byProperty->id()),
        ])->candidatesFor($distributor->id());

        $this->assertSame([(string) $byAccount->id(), (string) $byProperty->id(), (string) $own->id()], $this->idsOf($candidates));
    }

    public function testDoesNotDuplicateOwnPropertyAlsoDistributed(): void
    {
        $distributor = AccountMother::distributorAccount();
        $hotel = AccountMother::fromName('Hotel A');
        $property = $this->locatedProperty('Hotel', $hotel->id());

        $candidates = $this->createSUT([$distributor, $hotel], [
            DistributorAssignmentMother::forAccount($distributor->id(), $hotel->id()),
            DistributorAssignmentMother::forProperty($distributor->id(), $property->id()),
        ])->candidatesFor($distributor->id());

        $this->assertSame([(string) $property->id()], $this->idsOf($candidates));
    }

    public function testHotelWithoutTripCalculatorsFeatureIsStillACandidate(): void
    {
        $distributor = AccountMother::distributorAccount();
        $hotel = AccountMother::withFeatures('Hotel A', (string) AccountId::generate(), new AccountFeatures(propertyTripCalculators: false));
        $property = $this->locatedProperty('Hotel', $hotel->id());

        $candidates = $this->createSUT([$distributor, $hotel], [
            DistributorAssignmentMother::forAccount($distributor->id(), $hotel->id()),
        ])->candidatesFor($distributor->id());

        $this->assertSame([(string) $property->id()], $this->idsOf($candidates));
    }

    public function testAllPropertiesModeAllowsEveryCandidate(): void
    {
        $account = AccountMother::fromName('Agencia');
        $first = $this->locatedProperty('Hotel A', $account->id());
        $second = $this->locatedProperty('Hotel B', $account->id());
        $link = TripCalculationLinkMother::forAccountWithDestination((string) $account->id(), TripDestinationMode::AllProperties);

        $allowed = $this->createSUT([$account])->allowedFor($link);

        $this->assertSame([(string) $first->id(), (string) $second->id()], $this->idsOf($allowed));
    }

    public function testSelectedPropertiesModeExcludesThoseThatAreNoLongerCandidates(): void
    {
        $account = AccountMother::fromName('Agencia');
        $selected = $this->locatedProperty('Hotel A', $account->id());
        $this->locatedProperty('Hotel B', $account->id());
        $link = TripCalculationLinkMother::forAccountWithDestination(
            (string) $account->id(),
            TripDestinationMode::SelectedProperties,
            propertyIds: [$selected->id(), PropertyId::generate()],
        );

        $allowed = $this->createSUT([$account])->allowedFor($link);

        $this->assertSame([(string) $selected->id()], $this->idsOf($allowed));
    }

    public function testOtherModesDoNotAllowProperties(): void
    {
        $account = AccountMother::fromName('Agencia');
        $this->locatedProperty('Hotel A', $account->id());

        $link = TripCalculationLinkMother::forAccount((string) $account->id());

        $this->assertSame([], $this->createSUT([$account])->allowedFor($link));
    }

    private function locatedProperty(string $name, AccountId $accountId): Property
    {
        $property = PropertyMother::from(
            PropertyId::generate(),
            $name,
            $accountId,
            new PropertyInfo(latitude: '39.4699', longitude: '-0.3763'),
        );
        $this->propertyRepository->add($property);

        return $property;
    }

    /**
     * @param list<Property> $properties
     *
     * @return list<string>
     */
    private function idsOf(array $properties): array
    {
        return array_map(static fn(Property $property): string => (string) $property->id(), $properties);
    }

    /**
     * @param list<Account>               $accounts
     * @param list<DistributorAssignment> $assignments
     */
    private function createSUT(array $accounts, array $assignments = []): TripDestinationCandidates
    {
        return new TripDestinationCandidates(
            $this->propertyRepository,
            ConsultantSupervisionPolicyMother::create($accounts),
            new PropertyLocator(),
            DistributedPropertiesPolicyMother::create($accounts, [], $assignments),
        );
    }
}
