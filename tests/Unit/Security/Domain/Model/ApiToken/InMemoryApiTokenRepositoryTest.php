<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\ApiToken;

use App\Security\Domain\Model\ApiToken\ApiTokenRepository;

class InMemoryApiTokenRepositoryTest extends ApiTokenRepositoryTest
{
    protected function createSUT(): ApiTokenRepository
    {
        return ApiTokenRepositoryMother::inMemory();
    }
}
