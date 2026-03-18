<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\User;

use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;

trait GetUserFromIdTrait
{
    protected function getUserFromId(string $userId, UserRepository $userRepository): User
    {
        $user = $userRepository->find(new UserId($userId));

        if (!$user instanceof User) {
            throw $this->createNotFoundException('User not found');
        }

        return $user;
    }
}
