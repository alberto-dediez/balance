<?php

namespace App\Security\Infrastructure\UI\Web\User;

use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use Symfony\Component\Translation\TranslatableMessage;

trait GetUserFromIdTrait
{
    protected function getUserFromId(string $userId, UserRepository $userRepository): ?User
    {
        $user = $userRepository->find(new UserId($userId));

        if (null == $user) {
            throw $this->createNotFoundException(new TranslatableMessage("El usuario no existe"));
        }

        return $user;
    }
}
