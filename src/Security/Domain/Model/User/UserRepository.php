<?php

namespace App\Security\Domain\Model\User;

use App\Security\Domain\Model\User\UserId;

interface UserRepository
{
    public function add(User $user): void;

    public function remove(User $user): void;

    public function find(UserId $userId): ?User;

    public function findByUsername(string $username): ?User;
}