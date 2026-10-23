<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\TripCalculationLink;

use App\CarbonFootprint\Application\TripCalculationLink\TripCalculationLinkAuthorizer;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

class TripCalculationLinkAuthorizerTest extends TestCase
{
    private InMemoryPropertyRepository $propertyRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->propertyRepository = new InMemoryPropertyRepository();
    }

    public function testAdminCanDoEverything(): void
    {
        $link = $this->linkToPropertyOfAccount('account-id');
        $user = UserMother::adminUser();

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Remove] as $action) {
            $this->assertTrue($this->createSUT()->canDoThat($link, $action, $user));
        }
    }

    public function testOwnerCanDoEverythingOnALinkOfItsOwnAccount(): void
    {
        $link = $this->linkToPropertyOfAccount('account-id');
        $user = $this->ownerOf('account-id');

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Remove] as $action) {
            $this->assertTrue($this->createSUT()->canDoThat($link, $action, $user));
        }
    }

    public function testOwnerCannotDoAnythingOnALinkOfAnotherAccount(): void
    {
        $link = $this->linkToPropertyOfAccount('account-id');
        $user = $this->ownerOf('another-account-id');

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Remove] as $action) {
            $this->assertFalse($this->createSUT()->canDoThat($link, $action, $user));
        }
    }

    public function testUserCannotDoAnything(): void
    {
        $link = $this->linkToPropertyOfAccount('account-id');
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id'));

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Remove] as $action) {
            $this->assertFalse($this->createSUT()->canDoThat($link, $action, $user));
        }
    }

    public function testNobodyCanActOnALinkWhosePropertyDoesNotExist(): void
    {
        $link = TripCalculationLinkMother::fromPropertyIdAndAccount((string) PropertyId::generate(), 'account-id');

        $this->assertFalse($this->createSUT()->canDoThat($link, Action::View, UserMother::adminUser()));
        $this->assertFalse($this->createSUT()->canDoThat($link, Action::Edit, $this->ownerOf('account-id')));
    }

    public function testOwnerCannotActOnALinkWithoutPropertyNorAccount(): void
    {
        $link = new TripCalculationLink(TripCalculationLinkId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($link, Action::View, $this->ownerOf('account-id')));
    }

    public function testOwnerCanDoEverythingOnTheAccountLinkOfItsAccount(): void
    {
        $link = TripCalculationLinkMother::forAccount('account-id');
        $user = $this->ownerOf('account-id');

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Remove, Action::Export] as $action) {
            $this->assertTrue($this->createSUT()->canDoThat($link, $action, $user));
        }
    }

    public function testOwnerCannotDoAnythingOnTheAccountLinkOfAnotherAccount(): void
    {
        $link = TripCalculationLinkMother::forAccount('account-id');
        $user = $this->ownerOf('another-account-id');

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Remove, Action::Export] as $action) {
            $this->assertFalse($this->createSUT()->canDoThat($link, $action, $user));
        }
    }

    public function testOwnerActsOnTheAccountLinkOfTheCurrentAccount(): void
    {
        $link = TripCalculationLinkMother::forAccount('impersonated-account-id');
        $user = $this->ownerOf('account-id');
        $sut = new TripCalculationLinkAuthorizer(
            $this->propertyRepository,
            new PropertyAuthorizer(),
            new StubCurrentAccountIdProvider(new AccountId('impersonated-account-id')),
        );

        $this->assertTrue($sut->canDoThat($link, Action::Edit, $user));
    }

    public function testAdminCanDoEverythingOnAnAccountLink(): void
    {
        $link = TripCalculationLinkMother::forAccount('account-id');

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Remove, Action::Export] as $action) {
            $this->assertTrue($this->createSUT()->canDoThat($link, $action, UserMother::adminUser()));
        }
    }

    public function testUserCannotDoAnythingOnAnAccountLink(): void
    {
        $link = TripCalculationLinkMother::forAccount('account-id');
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id'));

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Remove, Action::Export] as $action) {
            $this->assertFalse($this->createSUT()->canDoThat($link, $action, $user));
        }
    }

    public function testExportFollowsViewOnPropertyLinks(): void
    {
        $link = $this->linkToPropertyOfAccount('account-id');

        $this->assertTrue($this->createSUT()->canDoThat($link, Action::Export, $this->ownerOf('account-id')));
        $this->assertFalse($this->createSUT()->canDoThat($link, Action::Export, $this->ownerOf('another-account-id')));
    }

    public function testOnlySupportsTripCalculationLinks(): void
    {
        $sut = $this->createSUT();

        $this->assertTrue($sut->supports(TripCalculationLinkMother::registeredTripCalculationLink()));
        $this->assertFalse($sut->supports(PropertyMother::registeredProperty()));
    }

    private function linkToPropertyOfAccount(string $accountId): TripCalculationLink
    {
        $property = PropertyMother::fromNameAndAccountAndId('Hotel', $accountId, (string) PropertyId::generate());
        $this->propertyRepository->add($property);

        return TripCalculationLinkMother::fromPropertyIdAndAccount((string) $property->id(), $accountId);
    }

    private function ownerOf(string $accountId): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId($accountId));
    }

    private function createSUT(): TripCalculationLinkAuthorizer
    {
        return new TripCalculationLinkAuthorizer($this->propertyRepository, new PropertyAuthorizer());
    }
}
