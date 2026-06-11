<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Event\PreventionPlan;

use App\FoodLoss\Domain\Event\PreventionPlan\CompletePreventionPlanOnDocumentSignedSubscriber;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersionStatus;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersionValidated;
use App\Security\Domain\Model\SignatureRequest\DocumentReference;
use App\Security\Domain\Model\SignatureRequest\DocumentSigned;
use App\Security\Domain\Model\SignatureRequest\SignatureProof;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanMother;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepositoryMother;
use PHPUnit\Framework\TestCase;

class CompletePreventionPlanOnDocumentSignedSubscriberTest extends TestCase
{
    private CompletePreventionPlanOnDocumentSignedSubscriber $sut;
    private PreventionPlanRepository $repository;
    private SignatureRequestId $signatureRequestId;
    private DocumentSigned $event;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->signatureRequestId = SignatureRequestId::generate();

        $this->repository = PreventionPlanRepositoryMother::inMemory();

        $plan = PreventionPlanMother::registeredPreventionPlan();
        $plan->requestSignature($this->signatureRequestId, $plan->currentVersion());
        $this->repository->add($plan);

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

        $this->sut = new CompletePreventionPlanOnDocumentSignedSubscriber($this->repository);
    }

    public function testIsSubscribedToDocumentSigned(): void
    {
        $this->assertTrue($this->sut->isSubscribedTo($this->event));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $this->assertFalse($this->sut->isSubscribedTo(new PreventionPlanVersionValidated(
            PreventionPlanId::generate(),
            1,
        )));
    }

    public function testCompletesPreventionPlanWhenDocumentIsSigned(): void
    {
        $this->sut->handle($this->event);

        $plan = $this->repository->findBySignatureRequestId($this->signatureRequestId);

        $this->assertNotNull($plan);
        $this->assertEquals(PreventionPlanVersionStatus::Completed, $plan->currentPlanVersion()->status());
    }

    public function testDoesNothingWhenPreventionPlanNotFound(): void
    {
        $unknownSignatureRequestId = SignatureRequestId::generate();

        $event = new DocumentSigned(
            signatureRequestId: $unknownSignatureRequestId,
            signatureProof: SignatureProof::create(
                signedAt: new \DateTimeImmutable(),
                documentHashAtSigning: 'abc123',
                signedDocumentSha256: 'sha256hash',
                driver: 'test',
                proofPayload: '{}',
            ),
            documentSigned: new DocumentReference(),
        );

        $this->sut->handle($event);

        $plan = $this->repository->findBySignatureRequestId($this->signatureRequestId);
        $this->assertNotNull($plan);
        $this->assertNotEquals(PreventionPlanVersionStatus::Completed, $plan->currentPlanVersion()->status());
    }
}
