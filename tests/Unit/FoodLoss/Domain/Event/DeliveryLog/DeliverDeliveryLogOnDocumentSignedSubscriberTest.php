<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Event\DeliveryLog;

use App\FoodLoss\Domain\Event\DeliveryLog\DeliverDeliveryLogOnDocumentSignedSubscriber;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRemoved;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\Security\Domain\Model\SignatureRequest\DocumentReference;
use App\Security\Domain\Model\SignatureRequest\DocumentSigned;
use App\Security\Domain\Model\SignatureRequest\SignatureProof;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepositoryMother;
use PHPUnit\Framework\TestCase;

class DeliverDeliveryLogOnDocumentSignedSubscriberTest extends TestCase
{
    private DeliverDeliveryLogOnDocumentSignedSubscriber $sut;
    private DeliveryLogRepository $repository;
    private SignatureRequestId $signatureRequestId;
    private DocumentSigned $event;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->signatureRequestId = SignatureRequestId::generate();
        $this->event = new DocumentSigned(
            signatureRequestId: $this->signatureRequestId,
            signatureProof: SignatureProof::create(
                signedAt: new \DateTimeImmutable(),
                documentHashAtSigning: 'abc123',
                signedDocumentSha256: 'sha256hash',
                driver: 'test',
                proofPayload: '{}',
            ),
            documentSigned: new DocumentReference(),
        );
        $this->repository = DeliveryLogRepositoryMother::inMemory();
        $this->sut = new DeliverDeliveryLogOnDocumentSignedSubscriber($this->repository);
    }

    public function testIsSubscribedToDocumentSigned(): void
    {
        $this->assertTrue($this->sut->isSubscribedTo($this->event));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $this->assertFalse($this->sut->isSubscribedTo(new DeliveryLogRemoved(DeliveryLogId::generate())));
    }

    public function testEndsSignatureProcessWhenDocumentSigned(): void
    {
        $deliveryLog = DeliveryLogMother::withSignatureRequestId($this->signatureRequestId);
        $this->repository->add($deliveryLog);

        $this->sut->handle($this->event);

        $this->assertTrue($deliveryLog->isDelivered());
    }

    public function testEndsSignatureProcessForMultipleDeliveryLogs(): void
    {
        $deliveryLog1 = DeliveryLogMother::withSignatureRequestId($this->signatureRequestId);
        $deliveryLog2 = DeliveryLogMother::withSignatureRequestId($this->signatureRequestId);
        $this->repository->add($deliveryLog1);
        $this->repository->add($deliveryLog2);

        $this->sut->handle($this->event);

        $this->assertTrue($deliveryLog1->isDelivered());
        $this->assertTrue($deliveryLog2->isDelivered());
    }

    public function testDoesNothingWhenNoDeliveryLogMatchesSignatureRequest(): void
    {
        $otherLog = DeliveryLogMother::withSignatureRequestId(SignatureRequestId::generate());
        $this->repository->add($otherLog);

        $this->sut->handle($this->event);

        $this->assertNotNull($this->repository->find($otherLog->id()));
        $this->assertFalse($otherLog->isDelivered());
    }
}
