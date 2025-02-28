<?php

namespace App\Security\Domain\Model\User;

use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserFilter;

interface UserRepository
{
    public function add(User $user): void;

    public function remove(User $user): void;

    public function find(UserId $userId): ?User;

    public function findByUsername(string $username): ?User;

    public function findByFilter(UserFilter $filter): array;

    public function totalResultsByFilter(UserFilter $filter): int;
}