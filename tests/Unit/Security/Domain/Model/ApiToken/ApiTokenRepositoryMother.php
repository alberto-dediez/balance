<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\ApiToken;

use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryApiTokenRepository;

class ApiTokenRepositoryMother
{
    public static function inMemory(): InMemoryApiTokenRepository
    {
        return new InMemoryApiTokenRepository();
    }
}
