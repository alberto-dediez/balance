<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Domain\Event\Provider;

use App\EventOperations\Application\Provider\LinkUser\LinkProviderUserService;
use App\EventOperations\Application\Provider\ProviderAuthorizer;
use App\EventOperations\Domain\Event\Provider\RegisterUserOnProviderRegisteredSubscriber;
use App\EventOperations\Domain\Model\Provider\Provider;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Domain\Model\Provider\ProviderRegistered;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;
use App\Security\Application\User\GrantRole\GrantRoleService;
use App\Security\Application\User\Register\RegisterUserService;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Notification\WelcomeUserNotifier;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\Symfony\Security\Md5PasswordHasher;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderRepositoryMother;
use App\Tests\Unit\Notifications\Application\Notification\Notify\SpySystemNotifier;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\Slugger\AsciiSlugger;

class RegisterUserOnProviderRegisteredSubscriberTest extends TestCase
{
    private InMemoryUserRepository $userRepository;
    private ProviderRepository $providerRepository;
    private SpySystemNotifier $notifier;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->userRepository = new InMemoryUserRepository();
        $this->providerRepository = ProviderRepositoryMother::inMemory();
        $this->notifier = new SpySystemNotifier();

        new WelcomeUserNotifier($this->notifier);
    }

    public function testRegistersAndLinksAProviderUserWithoutWelcome(): void
    {
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
        $this->userRepository->add($owner);

        $provider = ProviderMother::fromNameAndAccountAndId('Logística Sur', 'account-id', (string) ProviderId::generate());
        $this->providerRepository->add($provider);

        $this->createSut($owner)->handle($this->registeredEventOf($provider));

        $userId = $provider->userId();
        $this->assertNotNull($userId);

        $user = $this->userRepository->find($userId);
        $this->assertInstanceOf(User::class, $user);
        $this->assertTrue($user->hasRole(Role::Provider));
        $this->assertSame('logistica-sur', $user->username());
        $this->assertSame([], $this->notifier->notifications());
    }

    public function testDoesNothingWithoutLoggedUser(): void
    {
        $provider = ProviderMother::fromNameAndAccountAndId('Logística Sur', 'account-id', (string) ProviderId::generate());
        $this->providerRepository->add($provider);

        $this->createSut(null)->handle($this->registeredEventOf($provider));

        $this->assertNull($provider->userId());
        $this->assertSame([], $this->notifier->notifications());
    }

    private function registeredEventOf(Provider $provider): ProviderRegistered
    {
        return new ProviderRegistered(
            providerId: $provider->id(),
            name: $provider->name(),
            vatNumber: $provider->vatNumber(),
            address: $provider->address(),
            contact: $provider->contact(),
            accountId: $provider->accountId(),
        );
    }

    private function createSut(?User $loggedUser): RegisterUserOnProviderRegisteredSubscriber
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null === $loggedUser ? null : new SecurityUser($loggedUser));

        return new RegisterUserOnProviderRegisteredSubscriber(
            $this->userRepository,
            new RegisterUserService($this->userRepository, new Md5PasswordHasher(), UserAuthorizerMother::create()),
            new GrantRoleService($this->userRepository, UserAuthorizerMother::create()),
            new LinkProviderUserService($this->providerRepository, new ProviderAuthorizer()),
            new LoggedUser($security, $this->userRepository),
            new AsciiSlugger(),
        );
    }
}
