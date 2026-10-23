<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PublicLanding;

use App\CarbonFootprint\Application\PublicLanding\PublicLandingAuthorizer;
use App\CarbonFootprint\Application\TripCalculationLink\TripCalculationLinkAuthorizer;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLanding;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationLinkRepository;
use App\EventOperations\Application\Event\EventAuthorizer;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryEventRepository;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class PublicLandingAuthorizerTest extends TestCase
{
    private InMemoryPropertyRepository $propertyRepository;

    private InMemoryEventRepository $eventRepository;

    private InMemoryTripCalculationLinkRepository $tripCalculationLinkRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->propertyRepository = new InMemoryPropertyRepository();
        $this->eventRepository = new InMemoryEventRepository();
        $this->tripCalculationLinkRepository = new InMemoryTripCalculationLinkRepository();
    }

    public function testOwnerCanDoEverythingOnTheLandingOfItsOwnProperty(): void
    {
        $publicLanding = $this->landingOfPropertyOfAccount('account-id');
        $user = $this->ownerOf('account-id');

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Remove] as $action) {
            $this->assertTrue($this->createSUT()->canDoThat($publicLanding, $action, $user));
        }
    }

    public function testOwnerCannotTouchTheLandingOfAPropertyOfAnotherAccount(): void
    {
        $publicLanding = $this->landingOfPropertyOfAccount('account-id');
        $user = $this->ownerOf('another-account-id');

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Remove] as $action) {
            $this->assertFalse($this->createSUT()->canDoThat($publicLanding, $action, $user));
        }
    }

    public function testOwnerCanEditTheLandingOfItsOwnEvent(): void
    {
        $publicLanding = $this->landingOfEventOfAccount('account-id');

        $this->assertTrue($this->createSUT()->canDoThat($publicLanding, Action::Edit, $this->ownerOf('account-id')));
    }

    public function testOwnerCannotEditTheLandingOfAnEventOfAnotherAccount(): void
    {
        $publicLanding = $this->landingOfEventOfAccount('account-id');

        $this->assertFalse($this->createSUT()->canDoThat($publicLanding, Action::Edit, $this->ownerOf('another-account-id')));
    }

    public function testOwnerCanEditTheLandingOfTheTripCalculatorOfItsAccount(): void
    {
        $publicLanding = $this->landingOfTripCalculatorOfAccount('account-id');

        $this->assertTrue($this->createSUT()->canDoThat($publicLanding, Action::Edit, $this->ownerOf('account-id')));
    }

    public function testOwnerCannotEditTheLandingOfTheTripCalculatorOfAnotherAccount(): void
    {
        $publicLanding = $this->landingOfTripCalculatorOfAccount('account-id');

        $this->assertFalse($this->createSUT()->canDoThat($publicLanding, Action::Edit, $this->ownerOf('another-account-id')));
    }

    public function testPlainUserCannotDoAnything(): void
    {
        $publicLanding = $this->landingOfPropertyOfAccount('account-id');
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id'));

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Remove] as $action) {
            $this->assertFalse($this->createSUT()->canDoThat($publicLanding, $action, $user));
        }
    }

    public function testNobodyCanActOnALandingWhoseOwnerDoesNotExist(): void
    {
        $publicLanding = PublicLandingMother::forProperty((string) PropertyId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($publicLanding, Action::View, UserMother::adminUser()));
        $this->assertFalse($this->createSUT()->canDoThat($publicLanding, Action::Edit, $this->ownerOf('account-id')));
    }

    public function testNobodyCanActOnALandingWithoutOwner(): void
    {
        $publicLanding = new PublicLanding(PublicLandingId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($publicLanding, Action::View, UserMother::adminUser()));
    }

    public function testOnlySupportsPublicLandings(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->supports(PublicLandingMother::registeredPublicLanding()));
        $this->assertFalse($sut->supports(PropertyMother::registeredProperty()));
    }

    private function landingOfPropertyOfAccount(string $accountId): PublicLanding
    {
        $property = PropertyMother::fromNameAndAccountAndId('Hotel', $accountId, (string) PropertyId::generate());
        $this->propertyRepository->add($property);

        return PublicLandingMother::forProperty((string) $property->id(), $accountId);
    }

    private function landingOfEventOfAccount(string $accountId): PublicLanding
    {
        $event = EventMother::fromNameAndAccountAndId('Congreso', $accountId, (string) EventId::generate());
        $this->eventRepository->add($event);

        return PublicLandingMother::forEvent((string) $event->id(), $accountId);
    }

    private function landingOfTripCalculatorOfAccount(string $accountId): PublicLanding
    {
        $tripCalculationLink = TripCalculationLinkMother::forAccount($accountId);
        $this->tripCalculationLinkRepository->add($tripCalculationLink);

        return PublicLandingMother::forTripCalculationLink((string) $tripCalculationLink->id(), $accountId);
    }

    private function ownerOf(string $accountId): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId($accountId));
    }

    private function createSUT(): PublicLandingAuthorizer
    {
        return new PublicLandingAuthorizer(
            $this->propertyRepository,
            new PropertyAuthorizer(),
            $this->eventRepository,
            new EventAuthorizer(),
            $this->tripCalculationLinkRepository,
            new TripCalculationLinkAuthorizer($this->propertyRepository, new PropertyAuthorizer()),
        );
    }
}
