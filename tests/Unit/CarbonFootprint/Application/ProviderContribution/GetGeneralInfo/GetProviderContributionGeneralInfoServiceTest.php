<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ProviderContribution\GetGeneralInfo;

use App\CarbonFootprint\Application\ProviderContribution\GetGeneralInfo\GetProviderContributionGeneralInfoRequest;
use App\CarbonFootprint\Application\ProviderContribution\GetGeneralInfo\GetProviderContributionGeneralInfoService;
use App\CarbonFootprint\Application\ProviderContribution\ProviderContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContribution;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionTokenInvalidException;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryProviderContributionRepository;
use App\EventOperations\Application\Provider\LinkUser\LinkProviderUserRequest;
use App\EventOperations\Application\Provider\LinkUser\LinkProviderUserService;
use App\EventOperations\Application\Provider\ProviderAuthorizer;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryEventRepository;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryProviderRepository;
use App\Security\Application\User\GrantRole\GrantRoleRequest;
use App\Security\Application\User\GrantRole\GrantRoleService;
use App\Security\Application\User\Register\RegisterUserRequest;
use App\Security\Application\User\Register\RegisterUserService;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Security\Infrastructure\Symfony\Security\Md5PasswordHasher;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionMother;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GetProviderContributionGeneralInfoServiceTest extends TestCase
{
    private InMemoryProviderContributionRepository $providerContributionRepository;
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private InMemoryEventRepository $eventRepository;
    private InMemoryProviderRepository $providerRepository;
    private InMemoryUserRepository $userRepository;
    private GetProviderContributionGeneralInfoService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->providerContributionRepository = new InMemoryProviderContributionRepository();
        $this->eventCarbonFootprintRepository = new InMemoryEventCarbonFootprintRepository();
        $this->eventRepository = new InMemoryEventRepository();
        $this->providerRepository = new InMemoryProviderRepository();
        $this->userRepository = new InMemoryUserRepository();

        $this->sut = new GetProviderContributionGeneralInfoService(
            new ProviderContributionAuthenticator($this->providerContributionRepository, $this->providerRepository, $this->userRepository),
            $this->eventCarbonFootprintRepository,
            $this->eventRepository,
        );
    }

    public function testReturnsTheContributionAndItsEvent(): void
    {
        $event = EventMother::fromNameAndAccountAndId('Sample event', 'account-id', 'event-id');
        $this->eventRepository->add($event);

        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $contribution = $this->registeredContributionWithLinkedProviderUser((string) $eventCarbonFootprint->id());

        $response = ($this->sut)(new GetProviderContributionGeneralInfoRequest($contribution->token()));

        $this->assertTrue($response->providerContribution->id()->equals($contribution->id()));
        $this->assertSame('Sample event', $response->event->name());
    }

    public function testInvalidTokenThrows(): void
    {
        $this->expectException(ProviderContributionTokenInvalidException::class);
        ($this->sut)(new GetProviderContributionGeneralInfoRequest('invalid-token'));
    }

    public function testIgnoredContributionThrows(): void
    {
        $event = EventMother::fromNameAndAccountAndId('Sample event', 'account-id', 'event-id');
        $this->eventRepository->add($event);

        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $contribution = $this->registeredContributionWithLinkedProviderUser((string) $eventCarbonFootprint->id());
        $contribution->ignore();
        $this->providerContributionRepository->add($contribution);

        $this->expectException(ProviderContributionIgnoredException::class);
        ($this->sut)(new GetProviderContributionGeneralInfoRequest($contribution->token()));
    }

    private function registeredContributionWithLinkedProviderUser(string $eventCarbonFootprintId): ProviderContribution
    {
        $provider = ProviderMother::fromNameAndAccountAndId('Acme', 'account-id', (string) ProviderId::generate());
        $this->providerRepository->add($provider);

        $registerUser = new RegisterUserService($this->userRepository, new Md5PasswordHasher(), new UserAuthorizer());
        $grantRole = new GrantRoleService($this->userRepository, new UserAuthorizer());
        $linkProviderUser = new LinkProviderUserService($this->providerRepository, new ProviderAuthorizer());

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
        $registeredUser = $registerUser(new RegisterUserRequest('provider-' . uniqid() . '@contribution.internal', 'secret', $owner->accountId()), $owner);
        $grantRole(new GrantRoleRequest($registeredUser->userId, Role::Provider), $owner);
        $linkProviderUser(new LinkProviderUserRequest($provider->id(), $registeredUser->userId), $owner);

        $contribution = ProviderContributionMother::fromEventCarbonFootprintIdAndProviderId($eventCarbonFootprintId, (string) $provider->id());
        $this->providerContributionRepository->add($contribution);

        return $contribution;
    }
}
