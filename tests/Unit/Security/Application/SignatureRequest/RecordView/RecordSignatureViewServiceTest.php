<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\SignatureRequest\RecordView;

use App\Security\Application\SignatureRequest\RecordView\RecordSignatureViewRequest;
use App\Security\Application\SignatureRequest\RecordView\RecordSignatureViewService;
use App\Security\Domain\Model\SignatureRequest\SignatureDocumentViewed;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestStatus;
use App\Security\Domain\Model\SignatureRequest\SignatureTokenInvalidException;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestMother;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestRepositoryMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RecordSignatureViewServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();
    }

    public function testRecordsViewAndTransitionsToViewState(): void
    {
        $repository = SignatureRequestRepositoryMother::inMemory();
        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $token = $signatureRequest->token();
        $repository->add($signatureRequest);

        $sut = new RecordSignatureViewService(repository: $repository, appSecret: 'test-app-secret');

        $response = $sut(new RecordSignatureViewRequest(
            tokenPlainValue: $token->plainValue(),
            signerIp: '127.0.0.1',
            signerUserAgent: 'TestBrowser',
        ));

        $this->assertFalse($response->alreadySigned);
        $this->assertEquals(SignatureRequestStatus::Viewed, $response->signatureRequest->status());
        $this->assertInstanceOf(SignatureDocumentViewed::class, $this->spy->lastEvent());
    }

    public function testThrowsWhenTokenNotFound(): void
    {
        $this->expectException(SignatureTokenInvalidException::class);

        $repository = SignatureRequestRepositoryMother::inMemory();
        $sut = new RecordSignatureViewService(repository: $repository, appSecret: 'test-app-secret');

        $sut(new RecordSignatureViewRequest(tokenPlainValue: 'invalid-token-value'));
    }

    public function testReturnsAlreadySignedWhenSigned(): void
    {
        $repository = SignatureRequestRepositoryMother::inMemory();
        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $token = $signatureRequest->token();

        $proof = \App\Security\Domain\Model\SignatureRequest\SignatureProof::create(
            signedAt: new \DateTimeImmutable(),
            documentHashAtSigning: 'h',
            signedDocumentSha256: 'h2',
            driver: 'hmac',
            proofPayload: 'p',
        );
        $signatureRequest->sign($proof, new \App\Security\Domain\Model\SignatureRequest\DocumentReference());

        $repository->add($signatureRequest);

        $sut = new RecordSignatureViewService(repository: $repository, appSecret: 'test-app-secret');

        $response = $sut(new RecordSignatureViewRequest(tokenPlainValue: $token->plainValue()));

        $this->assertTrue($response->alreadySigned);
    }
}
