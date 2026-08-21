<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\TripCalculationLink\UpdateScope;

use App\CarbonFootprint\Application\TripCalculationLink\TripCalculationLinkAuthorizer;
use App\CarbonFootprint\Application\TripCalculationLink\UpdateScope\UpdateTripCalculationLinkScopeRequest;
use App\CarbonFootprint\Application\TripCalculationLink\UpdateScope\UpdateTripCalculationLinkScopeService;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkScopeUpdated;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationScope;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationLinkRepository;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class UpdateTripCalculationLinkScopeServiceTest extends TestCase
{
    private InMemoryTripCalculationLinkRepository $tripCalculationLinkRepository;

    private InMemoryPropertyRepository $propertyRepository;

    private UpdateTripCalculationLinkScopeService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->tripCalculationLinkRepository = new InMemoryTripCalculationLinkRepository();
        $this->propertyRepository = new InMemoryPropertyRepository();

        $this->sut = new UpdateTripCalculationLinkScopeService(
            $this->tripCalculationLinkRepository,
            new TripCalculationLinkAuthorizer($this->propertyRepository, new PropertyAuthorizer()),
        );
    }

    public function testUpdatesTheScope(): void
    {
        $tripCalculationLink = $this->registeredLink();

        $response = ($this->sut)(
            new UpdateTripCalculationLinkScopeRequest($tripCalculationLink->id(), TripCalculationScope::Transport),
            $this->ownerUser(),
        );

        $this->assertSame(TripCalculationScope::Transport, $response->tripCalculationLink->scope());

        $stored = $this->tripCalculationLinkRepository->find($tripCalculationLink->id());
        $this->assertNotNull($stored);
        $this->assertSame(TripCalculationScope::Transport, $stored->scope());

        $events = array_filter(
            $response->tripCalculationLink->recordedEvents(),
            static fn($event) => $event instanceof TripCalculationLinkScopeUpdated,
        );
        $this->assertNotEmpty($events);
    }

    public function testThrowsWhenTheLinkDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(
            new UpdateTripCalculationLinkScopeRequest(TripCalculationLinkId::generate(), TripCalculationScope::Transport),
            $this->ownerUser(),
        );
    }

    public function testDeniesAnOwnerFromAnotherAccount(): void
    {
        $tripCalculationLink = $this->registeredLink();

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new UpdateTripCalculationLinkScopeRequest($tripCalculationLink->id(), TripCalculationScope::Transport),
            UserMother::fromUsernameAndRoleAndAccount('intruder', Role::Owner, new AccountId('another-account-id')),
        );
    }

    private function registeredLink(): TripCalculationLink
    {
        $property = PropertyMother::fromNameAndAccountAndId('Hotel', 'account-id', (string) PropertyId::generate());
        $this->propertyRepository->add($property);

        $tripCalculationLink = TripCalculationLinkMother::fromPropertyIdAndAccount((string) $property->id(), 'account-id');
        $this->tripCalculationLinkRepository->add($tripCalculationLink);

        return $tripCalculationLink;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
