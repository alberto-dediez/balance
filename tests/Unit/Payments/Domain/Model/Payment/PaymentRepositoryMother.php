<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Domain\Model\Payment;

use App\Payments\Infrastructure\Persistence\InMemory\Repository\InMemoryPaymentRepository;

class PaymentRepositoryMother
{
    public static function inMemory(): InMemoryPaymentRepository
    {
        return new InMemoryPaymentRepository();
    }
}
