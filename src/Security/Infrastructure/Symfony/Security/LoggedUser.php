<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class LoggedUser
{
    private ?User $user = null;

    public function __construct(private readonly Security $security, private readonly UserRepository $userRepository) {}

    public function getLoggedUserOrNull(): ?User
    {
        if (!$this->user instanceof User) {
            $this->loadUser();
        }

        return $this->user;
    }

    public function __invoke(): User
    {
        if (!$this->user instanceof User) {
            $this->loadUser();
        }

        if (!$this->user instanceof User) {
            throw new \RuntimeException('Call to LoggedUser in a non authenticated environment');
        }

        return $this->user;
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
