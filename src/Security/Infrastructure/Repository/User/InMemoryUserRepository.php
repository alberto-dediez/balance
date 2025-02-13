<?php

namespace App\Security\Infrastructure\Repository\User;

use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryUserRepository implements UserRepository
{
    public function __construct(private array $users = [])
    {

    }

    public function add(User $user): void
    {
        if (isset($this->users[(string) $user->id()])) {
            return ;
        }

        $this->users[(string) $user->id()] = $user;
    }

    public function remove(User $user): void
    {
        if (!isset($this->users[(string) $user->id()])) {
            return ;
        }

        unset($this->users[(string) $user->id()]);
    }

    public function find(UserId $userId): ?User
    {
        return (isset($this->users[(string) $userId])) 
            ? $this->users[(string) $userId]
            : null
        ;
    }

    public function findByUsername(string $username): ?User
    {
        if (count($this->users) == 0) {
            return null;
        }

        foreach ($this->users as $user) {
            if ($user->username() == $username) {
                return $user;
            }
        }

        return null;
    }
}