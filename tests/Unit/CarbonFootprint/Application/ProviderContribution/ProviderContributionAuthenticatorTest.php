<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ProviderContribution;

use App\CarbonFootprint\Application\ProviderContribution\ProviderContributionAuthenticator;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionIgnoredException;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionTokenInvalidException;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryProviderContributionRepository;
use App\EventOperations\Application\Provider\LinkUser\LinkProviderUserRequest;
use App\EventOperations\Application\Provider\LinkUser\LinkProviderUserService;
use App\EventOperations\Application\Provider\ProviderAuthorizer;
use App\EventOperations\Domain\Model\Provider\Provider;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryProviderRepository;
use App\Security\Application\User\GrantRole\GrantRoleRequest;
use App\Security\Application\User\GrantRole\GrantRoleService;
use App\Security\Application\User\Register\RegisterUserRequest;
use App\Security\Application\User\Register\RegisterUserResponse;
use App\Security\Application\User\Register\RegisterUserService;
use App\Security\Application\User\UserAuthorizer;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Security\Infrastructure\Symfony\Security\Md5PasswordHasher;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ProviderContributionAuthenticatorTest extends TestCase
{
    private InMemoryProviderContributionRepository $providerContributionRepository;
    private InMemoryProviderRepository $providerRepository;
    private InMemoryUserRepository $userRepository;
    private ProviderContributionAuthenticator $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->providerContributionRepository = new InMemoryProviderContributionRepository();
        $this->providerRepository = new InMemoryProviderRepository();
        $this->userRepository = new InMemoryUserRepository();

        $this->sut = new ProviderContributionAuthenticator(
            $this->providerContributionRepository,
            $this->providerRepository,
            $this->userRepository,
        );
    }

    public function testAuthenticatesTheContributionAndItsProviderLinkedUser(): void
    {
        $provider = ProviderMother::fromNameAndAccountAndId('Acme', 'account-id', (string) ProviderId::generate());
        $this->providerRepository->add($provider);

        $registeredUser = $this->linkedProviderUser($provider);

        $contribution = ProviderContributionMother::fromEventCarbonFootprintIdAndProviderId('event-carbon-footprint-id', (string) $provider->id());
        $this->providerContributionRepository->add($contribution);

        $authentication = $this->sut->authenticate($contribution->token());

        $this->assertTrue($authentication->providerContribution->id()->equals($contribution->id()));
        $this->assertTrue($authentication->user->id()->equals($registeredUser->userId));
    }

    public function testInvalidTokenThrows(): void
    {
        $this->expectException(ProviderContributionTokenInvalidException::class);
        $this->sut->authenticate('invalid-token');
    }

    public function testIgnoredContributionThrows(): void
    {
        $provider = ProviderMother::fromNameAndAccountAndId('Acme', 'account-id', (string) ProviderId::generate());
        $this->providerRepository->add($provider);
        $this->linkedProviderUser($provider);

        $contribution = ProviderContributionMother::fromEventCarbonFootprintIdAndProviderId('event-carbon-footprint-id', (string) $provider->id());
        $contribution->ignore();
        $this->providerContributionRepository->add($contribution);

        $this->expectException(ProviderContributionIgnoredException::class);
        $this->sut->authenticate($contribution->token());
    }

    public function testProviderWithoutLinkedUserThrowsAsInvalidToken(): void
    {
        $provider = ProviderMother::fromNameAndAccountAndId('Acme', 'account-id', (string) ProviderId::generate());
        $this->providerRepository->add($provider);

        $contribution = ProviderContributionMother::fromEventCarbonFootprintIdAndProviderId('event-carbon-footprint-id', (string) $provider->id());
        $this->providerContributionRepository->add($contribution);

        $this->expectException(ProviderContributionTokenInvalidException::class);
        $this->sut->authenticate($contribution->token());
    }

    public function testLinkedUserNoLongerExistingThrowsAsInvalidToken(): void
    {
        $provider = ProviderMother::fromNameAndAccountAndId('Acme', 'account-id', (string) ProviderId::generate());
        $this->providerRepository->add($provider);
        $registeredUser = $this->linkedProviderUser($provider);
        $user = $this->userRepository->find($registeredUser->userId);
        $this->assertNotNull($user);
        $this->userRepository->remove($user);

        $contribution = ProviderContributionMother::fromEventCarbonFootprintIdAndProviderId('event-carbon-footprint-id', (string) $provider->id());
        $this->providerContributionRepository->add($contribution);

        $this->expectException(ProviderContributionTokenInvalidException::class);
        $this->sut->authenticate($contribution->token());
    }

    private function linkedProviderUser(Provider $provider): RegisterUserResponse
    {
        $registerUser = new RegisterUserService($this->userRepository, new Md5PasswordHasher(), new UserAuthorizer());
        $grantRole = new GrantRoleService($this->userRepository, new UserAuthorizer());
        $linkProviderUser = new LinkProviderUserService($this->providerRepository, new ProviderAuthorizer());

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
        $registeredUser = $registerUser(new RegisterUserRequest('provider-' . uniqid() . '@contribution.internal', 'secret', $owner->accountId()), $owner);
        $grantRole(new GrantRoleRequest($registeredUser->userId, Role::Provider), $owner);
        $linkProviderUser(new LinkProviderUserRequest($provider->id(), $registeredUser->userId), $owner);

        return $registeredUser;
    }
}
