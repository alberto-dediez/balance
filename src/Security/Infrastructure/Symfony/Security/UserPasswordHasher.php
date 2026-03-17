<?php

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Application\User\Register\PasswordHasher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserPasswordHasher implements PasswordHasher
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
        
    }

    public function hashPassword(string $password): string
    {
        return $this->passwordHasher->hashPassword(new SecurityUser(UserMother::registeredUser()), $password);
    }
}