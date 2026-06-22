<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Event\TrainingAttendance;

use App\FoodLoss\Domain\Event\TrainingAttendance\ConfirmTrainingAttendanceOnDocumentSignedSubscriber;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceId;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceSignatureCancelled;
use App\Security\Domain\Model\SignatureRequest\DocumentReference;
use App\Security\Domain\Model\SignatureRequest\DocumentSigned;
use App\Security\Domain\Model\SignatureRequest\SignatureProof;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceMother;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepositoryMother;
use PHPUnit\Framework\TestCase;

class ConfirmTrainingAttendanceOnDocumentSignedSubscriberTest extends TestCase
{
    private ConfirmTrainingAttendanceOnDocumentSignedSubscriber $sut;
    private TrainingAttendanceRepository $repository;
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
            documentSigned: new DocumentReference(path: '/signed/training-attendance.pdf'),
        );
        $this->repository = TrainingAttendanceRepositoryMother::inMemory();
        $this->sut = new ConfirmTrainingAttendanceOnDocumentSignedSubscriber($this->repository);
    }

    public function testIsSubscribedToDocumentSigned(): void
    {
        $this->assertTrue($this->sut->isSubscribedTo($this->event));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $this->assertFalse($this->sut->isSubscribedTo(new TrainingAttendanceSignatureCancelled(TrainingAttendanceId::generate())));
    }

    public function testEndsSignatureProcessWhenDocumentSigned(): void
    {
        $attendance = TrainingAttendanceMother::inSignatureProcess(
            signatureRequestId: $this->signatureRequestId,
        );
        $this->repository->add($attendance);

        $this->sut->handle($this->event);

        $this->assertFalse($attendance->isInSignatureProcess());
        $this->assertEquals('/signed/training-attendance.pdf', $attendance->filePath());
    }

    public function testEndsSignatureProcessForMultipleAttendances(): void
    {
        $attendance1 = TrainingAttendanceMother::inSignatureProcess(signatureRequestId: $this->signatureRequestId);
        $attendance2 = TrainingAttendanceMother::inSignatureProcess(signatureRequestId: $this->signatureRequestId);
        $this->repository->add($attendance1);
        $this->repository->add($attendance2);

        $this->sut->handle($this->event);

        $this->assertFalse($attendance1->isInSignatureProcess());
        $this->assertFalse($attendance2->isInSignatureProcess());
        $this->assertEquals('/signed/training-attendance.pdf', $attendance1->filePath());
        $this->assertEquals('/signed/training-attendance.pdf', $attendance2->filePath());
    }

    public function testDoesNothingWhenNoAttendanceMatchesSignatureRequest(): void
    {
        $otherAttendance = TrainingAttendanceMother::inSignatureProcess(
            signatureRequestId: SignatureRequestId::generate(),
        );
        $this->repository->add($otherAttendance);

        $this->sut->handle($this->event);

        $this->assertTrue($otherAttendance->isInSignatureProcess());
    }
}
