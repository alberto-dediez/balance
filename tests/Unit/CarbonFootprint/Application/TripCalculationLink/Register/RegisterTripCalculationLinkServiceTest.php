<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\TripCalculationLink\Register;

use App\CarbonFootprint\Application\TripCalculationLink\Register\RegisterTripCalculationLinkRequest;
use App\CarbonFootprint\Application\TripCalculationLink\Register\RegisterTripCalculationLinkService;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRegistered;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationLinkRepository;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RegisterTripCalculationLinkServiceTest extends TestCase
{
    private InMemoryTripCalculationLinkRepository $tripCalculationLinkRepository;

    private InMemoryPropertyRepository $propertyRepository;

    private RegisterTripCalculationLinkService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->tripCalculationLinkRepository = new InMemoryTripCalculationLinkRepository();
        $this->propertyRepository = new InMemoryPropertyRepository();

        $this->sut = new RegisterTripCalculationLinkService(
            $this->tripCalculationLinkRepository,
            $this->propertyRepository,
            new PropertyAuthorizer(),
        );
    }

    public function testRegistersALinkForANewProperty(): void
    {
        $property = PropertyMother::fromNameAndAccountAndId('Hotel', 'account-id', (string) PropertyId::generate());
        $this->propertyRepository->add($property);

        $response = ($this->sut)(new RegisterTripCalculationLinkRequest($property->id()), $this->ownerUser());

        $linkPropertyId = $response->tripCalculationLink->propertyId();
        $this->assertNotNull($linkPropertyId);
        $this->assertTrue($property->id()->equals($linkPropertyId));
        $this->assertNotSame('', $response->tripCalculationLink->token());

        $this->assertNotNull($this->tripCalculationLinkRepository->findByPropertyId($property->id()));

        $events = array_filter(
            $response->tripCalculationLink->recordedEvents(),
            static fn($event) => $event instanceof TripCalculationLinkRegistered,
        );
        $this->assertNotEmpty($events);
    }

    public function testIsIdempotentAndReturnsTheExistingLink(): void
    {
        $property = PropertyMother::fromNameAndAccountAndId('Hotel', 'account-id', (string) PropertyId::generate());
        $this->propertyRepository->add($property);

        $first = ($this->sut)(new RegisterTripCalculationLinkRequest($property->id()), $this->ownerUser());
        $second = ($this->sut)(new RegisterTripCalculationLinkRequest($property->id()), $this->ownerUser());

        $this->assertTrue($first->tripCalculationLink->id()->equals($second->tripCalculationLink->id()));
        $this->assertSame($first->tripCalculationLink->token(), $second->tripCalculationLink->token());
    }

    public function testThrowsWhenThePropertyDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new RegisterTripCalculationLinkRequest(PropertyId::generate()), $this->ownerUser());
    }

    public function testThrowsWhenThePropertyHasNoAccount(): void
    {
        $property = PropertyMother::fromNameAndId('Hotel', (string) PropertyId::generate());
        $this->propertyRepository->add($property);

        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new RegisterTripCalculationLinkRequest($property->id()), $this->adminUser());
    }

    public function testDeniesAnOwnerFromAnotherAccount(): void
    {
        $property = PropertyMother::fromNameAndAccountAndId('Hotel', 'account-id', (string) PropertyId::generate());
        $this->propertyRepository->add($property);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new RegisterTripCalculationLinkRequest($property->id()),
            UserMother::fromUsernameAndRoleAndAccount('intruder', Role::Owner, new AccountId('another-account-id')),
        );
    }

    public function testDeniesAnOwnerFromAnotherAccountWhenTheLinkAlreadyExists(): void
    {
        $property = PropertyMother::fromNameAndAccountAndId('Hotel', 'account-id', (string) PropertyId::generate());
        $this->propertyRepository->add($property);

        ($this->sut)(new RegisterTripCalculationLinkRequest($property->id()), $this->ownerUser());

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new RegisterTripCalculationLinkRequest($property->id()),
            UserMother::fromUsernameAndRoleAndAccount('intruder', Role::Owner, new AccountId('another-account-id')),
        );
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }

    private function adminUser(): User
    {
        return UserMother::adminUser();
    }
}
