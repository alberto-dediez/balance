<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Event\DeliveryLog;

use App\FoodLoss\Domain\Event\DeliveryLog\CancelDeliveryLogSignatureOnBadSignatureSubscriber;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRemoved;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestExpired;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepositoryMother;
use PHPUnit\Framework\TestCase;

class CancelDeliveryLogSignatureOnBadSignatureSubscriberTest extends TestCase
{
    private CancelDeliveryLogSignatureOnBadSignatureSubscriber $sut;
    private DeliveryLogRepository $repository;
    private SignatureRequestId $signatureRequestId;
    private SignatureRequestExpired $expiredEvent;
    private SignatureRequestRemoved $removedEvent;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->signatureRequestId = SignatureRequestId::generate();
        $this->expiredEvent = new SignatureRequestExpired($this->signatureRequestId);
        $this->removedEvent = new SignatureRequestRemoved($this->signatureRequestId);
        $this->repository = DeliveryLogRepositoryMother::inMemory();
        $this->sut = new CancelDeliveryLogSignatureOnBadSignatureSubscriber($this->repository);
    }

    public function testIsSubscribedToSignatureRequestExpired(): void
    {
        $this->assertTrue($this->sut->isSubscribedTo($this->expiredEvent));
    }

    public function testIsSubscribedToSignatureRequestRemoved(): void
    {
        $this->assertTrue($this->sut->isSubscribedTo($this->removedEvent));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $this->assertFalse($this->sut->isSubscribedTo(new DeliveryLogRemoved(DeliveryLogId::generate())));
    }

    public function testCancelsSignatureProcessOnExpiredEvent(): void
    {
        $deliveryLog = DeliveryLogMother::withSignatureRequestId($this->signatureRequestId);
        $this->repository->add($deliveryLog);

        $this->sut->handle($this->expiredEvent);

        $this->assertNull($deliveryLog->signatureRequestId());
        $this->assertFalse($deliveryLog->isInSignatureProcess());
    }

    public function testCancelsSignatureProcessOnRemovedEvent(): void
    {
        $deliveryLog = DeliveryLogMother::withSignatureRequestId($this->signatureRequestId);
        $this->repository->add($deliveryLog);

        $this->sut->handle($this->removedEvent);

        $this->assertNull($deliveryLog->signatureRequestId());
        $this->assertFalse($deliveryLog->isInSignatureProcess());
    }

    public function testCancelsMultipleDeliveryLogsWithSameSignatureRequest(): void
    {
        $deliveryLog1 = DeliveryLogMother::withSignatureRequestId($this->signatureRequestId);
        $deliveryLog2 = DeliveryLogMother::withSignatureRequestId($this->signatureRequestId);
        $this->repository->add($deliveryLog1);
        $this->repository->add($deliveryLog2);

        $this->sut->handle($this->expiredEvent);

        $this->assertNull($deliveryLog1->signatureRequestId());
        $this->assertNull($deliveryLog2->signatureRequestId());
    }

    public function testDoesNothingWhenNoDeliveryLogMatchesSignatureRequest(): void
    {
        $otherLog = DeliveryLogMother::withSignatureRequestId(SignatureRequestId::generate());
        $this->repository->add($otherLog);

        $this->sut->handle($this->expiredEvent);

        $this->assertNotNull($this->repository->find($otherLog->id()));
        $this->assertTrue($otherLog->isInSignatureProcess());
    }
}
