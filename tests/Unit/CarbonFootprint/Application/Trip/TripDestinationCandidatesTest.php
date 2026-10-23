<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Trip;

use App\CarbonFootprint\Application\Trip\PropertyLocator;
use App\CarbonFootprint\Application\Trip\TripDestinationCandidates;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationMode;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkMother;
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

    /** @param list<Account> $accounts */
    private function createSUT(array $accounts): TripDestinationCandidates
    {
        return new TripDestinationCandidates(
            $this->propertyRepository,
            ConsultantSupervisionPolicyMother::create($accounts),
            new PropertyLocator(),
        );
    }
}
