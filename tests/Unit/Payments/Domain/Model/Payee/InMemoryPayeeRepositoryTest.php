<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Domain\Model\Payee;

use App\Payments\Domain\Model\Payee\PayeeRepository;

class InMemoryPayeeRepositoryTest extends PayeeRepositoryTest
{
    protected function createSUT(): PayeeRepository
    {
        return PayeeRepositoryMother::inMemory();
    }
}
