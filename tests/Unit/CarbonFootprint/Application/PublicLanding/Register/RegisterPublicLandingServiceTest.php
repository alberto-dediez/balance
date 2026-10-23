<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PublicLanding\Register;

use App\CarbonFootprint\Application\PublicLanding\PublicLandingOwnerLocator;
use App\CarbonFootprint\Application\PublicLanding\Register\RegisterPublicLandingRequest;
use App\CarbonFootprint\Application\PublicLanding\Register\RegisterPublicLandingService;
use App\CarbonFootprint\Application\TripCalculationLink\TripCalculationLinkAuthorizer;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingOwnerType;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingRegistered;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPublicLandingRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationLinkRepository;
use App\EventOperations\Application\Event\EventAuthorizer;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryEventRepository;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RegisterPublicLandingServiceTest extends TestCase
{
    private InMemoryPublicLandingRepository $publicLandingRepository;

    private InMemoryPropertyRepository $propertyRepository;

    private InMemoryEventRepository $eventRepository;

    private InMemoryTripCalculationLinkRepository $tripCalculationLinkRepository;

    private RegisterPublicLandingService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->publicLandingRepository = new InMemoryPublicLandingRepository();
        $this->propertyRepository = new InMemoryPropertyRepository();
        $this->eventRepository = new InMemoryEventRepository();
        $this->tripCalculationLinkRepository = new InMemoryTripCalculationLinkRepository();

        $this->sut = new RegisterPublicLandingService(
            $this->publicLandingRepository,
            new PublicLandingOwnerLocator(
                $this->propertyRepository,
                new PropertyAuthorizer(),
                $this->eventRepository,
                new EventAuthorizer(),
                $this->tripCalculationLinkRepository,
                new TripCalculationLinkAuthorizer($this->propertyRepository, new PropertyAuthorizer()),
            ),
        );
    }

    public function testRegistersAnEmptyLandingForAProperty(): void
    {
        $property = PropertyMother::fromNameAndAccountAndId('Hotel', 'account-id', (string) PropertyId::generate());
        $this->propertyRepository->add($property);

        $response = ($this->sut)(
            new RegisterPublicLandingRequest(PublicLandingOwnerType::Property, (string) $property->id()),
            $this->ownerUser(),
        );

        $this->assertSame(PublicLandingOwnerType::Property, $response->publicLanding->ownerType());
        $this->assertSame((string) $property->id(), $response->publicLanding->ownerId());
        $this->assertFalse($response->publicLanding->isActive());

        $this->assertNotNull(
            $this->publicLandingRepository->findByOwner(PublicLandingOwnerType::Property, (string) $property->id()),
        );

        $events = array_filter(
            $response->publicLanding->recordedEvents(),
            static fn($event) => $event instanceof PublicLandingRegistered,
        );
        $this->assertNotEmpty($events);
    }

    public function testRegistersAnEmptyLandingForAnEvent(): void
    {
        $event = EventMother::fromNameAndAccountAndId('Congreso', 'account-id', (string) EventId::generate());
        $this->eventRepository->add($event);

        $response = ($this->sut)(
            new RegisterPublicLandingRequest(PublicLandingOwnerType::Event, (string) $event->id()),
            $this->ownerUser(),
        );

        $this->assertSame(PublicLandingOwnerType::Event, $response->publicLanding->ownerType());
        $this->assertSame((string) $event->id(), $response->publicLanding->ownerId());
    }

    public function testRegistersAnEmptyLandingForATripCalculator(): void
    {
        $tripCalculationLink = TripCalculationLinkMother::forAccount('account-id');
        $this->tripCalculationLinkRepository->add($tripCalculationLink);

        $response = ($this->sut)(
            new RegisterPublicLandingRequest(PublicLandingOwnerType::TripCalculationLink, (string) $tripCalculationLink->id()),
            $this->ownerUser(),
        );

        $this->assertSame(PublicLandingOwnerType::TripCalculationLink, $response->publicLanding->ownerType());
        $this->assertSame((string) $tripCalculationLink->id(), $response->publicLanding->ownerId());
        $accountId = $response->publicLanding->accountId();
        $this->assertNotNull($accountId);
        $this->assertTrue((new AccountId('account-id'))->equals($accountId));
    }

    public function testDeniesAnOwnerFromAnotherAccountOnATripCalculator(): void
    {
        $tripCalculationLink = TripCalculationLinkMother::forAccount('account-id');
        $this->tripCalculationLinkRepository->add($tripCalculationLink);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new RegisterPublicLandingRequest(PublicLandingOwnerType::TripCalculationLink, (string) $tripCalculationLink->id()),
            UserMother::fromUsernameAndRoleAndAccount('intruder', Role::Owner, new AccountId('another-account-id')),
        );
    }

    public function testIsIdempotentAndReturnsTheExistingLanding(): void
    {
        $property = PropertyMother::fromNameAndAccountAndId('Hotel', 'account-id', (string) PropertyId::generate());
        $this->propertyRepository->add($property);

        $request = new RegisterPublicLandingRequest(PublicLandingOwnerType::Property, (string) $property->id());

        $first = ($this->sut)($request, $this->ownerUser());
        $second = ($this->sut)($request, $this->ownerUser());

        $this->assertTrue($first->publicLanding->id()->equals($second->publicLanding->id()));
    }

    public function testThrowsWhenTheOwnerDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(
            new RegisterPublicLandingRequest(PublicLandingOwnerType::Property, (string) PropertyId::generate()),
            $this->ownerUser(),
        );
    }

    public function testDeniesAnOwnerFromAnotherAccount(): void
    {
        $property = PropertyMother::fromNameAndAccountAndId('Hotel', 'account-id', (string) PropertyId::generate());
        $this->propertyRepository->add($property);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new RegisterPublicLandingRequest(PublicLandingOwnerType::Property, (string) $property->id()),
            UserMother::fromUsernameAndRoleAndAccount('intruder', Role::Owner, new AccountId('another-account-id')),
        );
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
