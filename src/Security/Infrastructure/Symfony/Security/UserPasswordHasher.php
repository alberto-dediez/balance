<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security;

use App\Security\Application\User\Register\PasswordHasher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserPasswordHasher implements PasswordHasher
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher) {}

    public function hashPassword(string $password): string
    {
        return $this->passwordHasher->hashPassword(new SecurityUser(UserMother::registeredUser()), $password);
    }
}
