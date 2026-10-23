<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\EmailLog;

use App\Security\Domain\Model\EmailLog\EmailLogRepository;

class InMemoryEmailLogRepositoryTest extends EmailLogRepositoryTest
{
    protected function createSUT(): EmailLogRepository
    {
        return EmailLogRepositoryMother::inMemory();
    }
}
