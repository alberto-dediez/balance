<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog;

use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Shared\Domain\Event\DomainEventPublisher;
use PHPUnit\Framework\TestCase;

class DeliveryLogTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testThrowsWhenCancellingLogNotInSignatureProcess(): void
    {
        $log = DeliveryLogMother::registeredDeliveryLog();

        $this->expectException(\InvalidArgumentException::class);

        $log->cancelSignatureProcess();
    }

    public function testThrowsWhenCancellingAlreadyCancelledLog(): void
    {
        $log = DeliveryLogMother::registeredDeliveryLog();
        $log->startSignatureProcess(SignatureRequestId::generate(), new \DateTimeImmutable());
        $log->cancelSignatureProcess();

        $this->expectException(\InvalidArgumentException::class);

        $log->cancelSignatureProcess();
    }
}
