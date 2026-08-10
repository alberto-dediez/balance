<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Domain\Model\Payment;

use App\Payments\Domain\Model\Payment\PaymentRepository;

class InMemoryPaymentRepositoryTest extends PaymentRepositoryTest
{
    protected function createSUT(): PaymentRepository
    {
        return PaymentRepositoryMother::inMemory();
    }
}
