<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User\Register;

use App\Security\Application\User\Register\PasswordHasher;

class SpyPasswordHasher implements PasswordHasher
{
    /** @var string[] */
    private array $passwords = [];

    public function hashPassword(string $password): string
    {
        $this->passwords[] = $password;

        return md5($password);
    }

    /** @return string[] */
    public function passwords(): array
    {
        return $this->passwords;
    }
}
