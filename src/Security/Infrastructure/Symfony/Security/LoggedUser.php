<?php

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Domain\Model\User\User;
use Symfony\Bundle\SecurityBundle\Security;
use App\Security\Domain\Model\User\UserRepository;

class LoggedUser
{
    private ?User $user = null;

    private ?User $forcedUser = null;

    public function __construct(private Security $security, private UserRepository $userRepository)
    {

    }

    public function __invoke(): ?User
    {
        if (null !== $this->forcedUser) {
            return $this->forcedUser;
        }

        if (null == $this->user) {
            $this->loadUser();
        }

        return $this->user;
    }

    public function forceUser(?User $user): void
    {
        $this->forcedUser = $user;
    }

    private function loadUser()
    {
        $user = $this->security->getUser();

        if (null === $user) {
            return ;
        }

        /** @var User $user */

        $this->user = $this->userRepository->find($user->id());
    }
}
