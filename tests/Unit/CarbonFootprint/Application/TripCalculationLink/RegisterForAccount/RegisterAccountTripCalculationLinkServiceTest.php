<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\TripCalculationLink\RegisterForAccount;

use App\CarbonFootprint\Application\TripCalculationLink\RegisterForAccount\RegisterAccountTripCalculationLinkRequest;
use App\CarbonFootprint\Application\TripCalculationLink\RegisterForAccount\RegisterAccountTripCalculationLinkService;
use App\CarbonFootprint\Application\TripCalculationLink\TripCalculationLinkAuthorizer;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRegistered;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryTripCalculationLinkRepository;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

class RegisterAccountTripCalculationLinkServiceTest extends TestCase
{
    private InMemoryTripCalculationLinkRepository $tripCalculationLinkRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->tripCalculationLinkRepository = new InMemoryTripCalculationLinkRepository();
    }

    public function testRegistersTheLinkOfTheCurrentAccount(): void
    {
        $response = ($this->createSUT('account-id'))(new RegisterAccountTripCalculationLinkRequest(), $this->ownerOf('account-id'));

        $link = $response->tripCalculationLink;
        $accountId = $link->accountId();

        $this->assertNull($link->propertyId());
        $this->assertNotNull($accountId);
        $this->assertTrue((new AccountId('account-id'))->equals($accountId));
        $this->assertNotSame('', $link->token());
        $this->assertNotNull($this->tripCalculationLinkRepository->findForAccount(new AccountId('account-id')));

        $events = array_filter(
            $link->recordedEvents(),
            static fn(object $event): bool => $event instanceof TripCalculationLinkRegistered,
        );
        $this->assertNotEmpty($events);
    }

    public function testIsIdempotentAndReturnsTheExistingLink(): void
    {
        $sut = $this->createSUT('account-id');

        $first = $sut(new RegisterAccountTripCalculationLinkRequest(), $this->ownerOf('account-id'));
        $second = $sut(new RegisterAccountTripCalculationLinkRequest(), $this->ownerOf('account-id'));

        $this->assertTrue($first->tripCalculationLink->id()->equals($second->tripCalculationLink->id()));
        $this->assertSame($first->tripCalculationLink->token(), $second->tripCalculationLink->token());
    }

    public function testDeniesAUserWithoutOwnerRole(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        ($this->createSUT('account-id'))(
            new RegisterAccountTripCalculationLinkRequest(),
            UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id')),
        );
    }

    public function testRegistersTheLinkOfTheImpersonatedAccount(): void
    {
        $response = ($this->createSUT('impersonated-account-id'))(new RegisterAccountTripCalculationLinkRequest(), $this->ownerOf('account-id'));

        $accountId = $response->tripCalculationLink->accountId();

        $this->assertNotNull($accountId);
        $this->assertTrue((new AccountId('impersonated-account-id'))->equals($accountId));
        $this->assertNull($this->tripCalculationLinkRepository->findForAccount(new AccountId('account-id')));
    }

    public function testThrowsWithoutCurrentAccount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->createSUT(null))(new RegisterAccountTripCalculationLinkRequest(), UserMother::adminUser());
    }

    private function ownerOf(string $accountId): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId($accountId));
    }

    private function createSUT(?string $currentAccountId): RegisterAccountTripCalculationLinkService
    {
        $currentAccountIdProvider = new StubCurrentAccountIdProvider(null !== $currentAccountId ? new AccountId($currentAccountId) : null);

        return new RegisterAccountTripCalculationLinkService(
            $this->tripCalculationLinkRepository,
            new TripCalculationLinkAuthorizer(new InMemoryPropertyRepository(), new PropertyAuthorizer(), $currentAccountIdProvider),
            $currentAccountIdProvider,
        );
    }
}
