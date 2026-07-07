<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Service\ResetInterface;

class LoggedUser implements ResetInterface
{
    private ?User $user = null;

    private ?User $forcedUser = null;

    public function __construct(private readonly Security $security, private readonly UserRepository $userRepository) {}

    public function getLoggedUserOrNull(): ?User
    {
        try {
            return $this->__invoke();
        } catch (\Throwable) {
            return null;
        }
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
}
