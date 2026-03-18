<?php

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Domain\Model\User\User;
use Symfony\Bundle\SecurityBundle\Security;
use App\Security\Domain\Model\User\UserRepository;

class LoggedUser
{
    private ?User $user = null;

    public function __construct(private readonly Security $security, private readonly UserRepository $userRepository)
    {

    }

    public function getLoggedUserOrNull(): ?User
    {
        if (null === $this->user) {
            $this->loadUser();
        }

        return $this->user;
    }

    public function __invoke(): User
    {
        if (null === $this->user) {
            $this->loadUser();
        }

        if (null === $this->user) {
            throw new \RuntimeException("Call to LoggedUser in a non authenticated environment");
        }

        return $this->user;
    }

    private function loadUser(): void
    {
        $user = $this->security->getUser();

        if (null === $user) {
            return ;
        }

        /** @var SecurityUser $user */

        $this->user = $this->userRepository->find($user->id());
    }
}
