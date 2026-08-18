<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Domain\Model\Payee;

use App\Payments\Infrastructure\Persistence\InMemory\Repository\InMemoryPayeeRepository;

class PayeeRepositoryMother
{
    public static function inMemory(): InMemoryPayeeRepository
    {
        return new InMemoryPayeeRepository();
    }
}
