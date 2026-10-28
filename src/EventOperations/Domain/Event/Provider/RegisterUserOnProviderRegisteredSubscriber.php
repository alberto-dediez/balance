<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Event\Provider;

use App\EventOperations\Application\Provider\LinkUser\LinkProviderUserRequest;
use App\EventOperations\Application\Provider\LinkUser\LinkProviderUserService;
use App\EventOperations\Domain\Model\Provider\ProviderRegistered;
use App\Security\Application\User\GrantRole\GrantRoleRequest;
use App\Security\Application\User\GrantRole\GrantRoleService;
use App\Security\Application\User\Register\RegisterUserRequest;
use App\Security\Application\User\Register\RegisterUserService;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use App\Shared\Domain\Uuid\UuidGenerator;
use Symfony\Component\String\Slugger\SluggerInterface;

class RegisterUserOnProviderRegisteredSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private UserRepository $userRepository,
        private RegisterUserService $registerUser,
        private GrantRoleService $grantRole,
        private LinkProviderUserService $linkProviderUser,
        private LoggedUser $loggedUser,
        private SluggerInterface $slugger,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof ProviderRegistered);
        $this->handleProviderRegistered($event);
    }

    private function handleProviderRegistered(ProviderRegistered $event): void
    {
        $performBy = $this->loggedUser->getLoggedUserOrNull();

        if (!$performBy instanceof User) {
            return;
        }

        $registerUser = $this->registerUser;
        $registerResponse = $registerUser(
            new RegisterUserRequest(
                username: $this->generateUsername($event->name()),
                password: UuidGenerator::v4(),
                accountId: $event->accountId(),
                welcome: false,
            ),
            $performBy,
        );

        $grantRole = $this->grantRole;
        $grantRole(
            new GrantRoleRequest($registerResponse->userId, Role::Provider),
            $performBy,
        );

        $linkProviderUser = $this->linkProviderUser;
        $linkProviderUser(
            new LinkProviderUserRequest($event->providerId(), $registerResponse->userId),
            $performBy,
        );
    }

    private function generateUsername(string $name): string
    {
        $base = $this->slugger->slug($name)->lower()->toString();

        if ('' === $base) {
            $base = 'provider';
        }

        $username = $base;
        $suffix = 1;

        while ($this->userRepository->findByUsername($username) instanceof User) {
            ++$suffix;
            $username = sprintf('%s-%d', $base, $suffix);
        }

        return $username;
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof ProviderRegistered;
    }
}
