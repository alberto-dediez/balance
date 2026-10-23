<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\EmailLog;

use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryEmailLogRepository;

class EmailLogRepositoryMother
{
    public static function inMemory(): InMemoryEmailLogRepository
    {
        return new InMemoryEmailLogRepository();
    }
}
