<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Service\ResetInterface;

class LoggedUser implements ResetInterface
{
    private ?User $user = null;

    private ?User $forcedUser = null;

    private ?User $impersonator = null;

    private ?User $forcedImpersonator = null;

    public function __construct(private readonly Security $security, private readonly UserRepository $userRepository) {}

    public function getLoggedUserOrNull(): ?User
    {
        try {
            return $this->__invoke();
        } catch (\Throwable) {
            return null;
        }
    }

    public function getImpersonatorOrNull(): ?User
    {
        if ($this->forcedImpersonator instanceof User) {
            return $this->forcedImpersonator;
        }

        if (!$this->impersonator instanceof User) {
            $this->loadImpersonator();
        }

        return $this->impersonator;
    }

    public function isImpersonated(): bool
    {
        return $this->getImpersonatorOrNull() instanceof User;
    }

    public function forceImpersonator(?User $user): void
    {
        $this->forcedImpersonator = $user;
    }

    public function __invoke(): User
    {
        if ($this->forcedUser instanceof User) {
            return $this->forcedUser;
        }

        if (!$this->user instanceof User) {
            $this->loadUser();
        }

        if (!$this->user instanceof User) {
            throw new \RuntimeException('Call to LoggedUser in a non authenticated environment');
        }

        return $this->user;
    }

    public function forceUser(?User $user): void
    {
        $this->forcedUser = $user;
    }

    public function reset(): void
    {
        $this->user = null;
        $this->forcedUser = null;
        $this->impersonator = null;
        $this->forcedImpersonator = null;
    }

    private function loadUser(): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        assert($user instanceof SecurityUser);

        $this->user = $this->userRepository->find($user->id());
    }

    private function loadImpersonator(): void
    {
        $token = $this->security->getToken();

        if (!$token instanceof SwitchUserToken) {
            return;
        }

        $user = $token->getOriginalToken()->getUser();

        if (!$user instanceof SecurityUser) {
            return;
        }

        $this->impersonator = $this->userRepository->find($user->id());
    }
}
