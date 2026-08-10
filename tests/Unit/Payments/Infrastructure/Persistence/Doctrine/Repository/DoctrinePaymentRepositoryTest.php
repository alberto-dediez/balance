<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Infrastructure\Persistence\Doctrine\Repository;

use App\Payments\Domain\Model\Payment\PaymentRepository;
use App\Payments\Infrastructure\Persistence\Doctrine\Repository\DoctrinePaymentRepository;
use App\Tests\Unit\Payments\Domain\Model\Payment\PaymentRepositoryTest;
use Doctrine\ORM\EntityManagerInterface;

class DoctrinePaymentRepositoryTest extends PaymentRepositoryTest
{
    protected function createSUT(): PaymentRepository
    {
        $manager = $this->getContainer()->get(EntityManagerInterface::class);

        return new DoctrinePaymentRepository($manager);
    }
}
