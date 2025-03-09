<?php

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Domain\Model\User\User;
use Symfony\Bundle\SecurityBundle\Security;
use App\Security\Domain\Model\User\UserRepository;

class LoggedUser
{
    private ?User $user = null;

    public function __construct(Security $security, UserRepository $userRepository)
    {
        $user = $security->getUser();

        if (null == $user) {
            return ;
        }

        $this->user = $userRepository->find($user->id());
    }

    public function __invoke()
    {
        return $this->user;   
    }
}