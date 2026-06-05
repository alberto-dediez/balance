<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\SignatureRequest\SendOtp;

use App\Security\Application\SignatureRequest\SendOtp\SendOtpRequest;
use App\Security\Application\SignatureRequest\SendOtp\SendOtpService;
use App\Security\Domain\Model\SignatureRequest\NotificationChannel;
use App\Security\Domain\Model\SignatureRequest\OtpChallenge;
use App\Security\Domain\Model\SignatureRequest\SignatureOtpRequested;
use App\Security\Domain\Model\SignatureRequest\SignatureTokenInvalidException;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestMother;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestRepositoryMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class SendOtpServiceTest extends TestCase
{
    private const APP_SECRET = 'test-app-secret';

    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();
    }

    public function testSendsOtpAndFiresEventForValidToken(): void
    {
        $repository = SignatureRequestRepositoryMother::inMemory();
        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $token = $signatureRequest->token();
        $repository->add($signatureRequest);

        $sut = new SendOtpService(repository: $repository, appSecret: self::APP_SECRET);

        $response = $sut(new SendOtpRequest(
            tokenPlainValue: $token->plainValue(),
            signerIp: '127.0.0.1',
        ));

        $this->assertFalse($response->alreadyVerified);
        $this->assertEquals(NotificationChannel::Email, $response->notificationChannel);
        $this->assertNotEmpty($response->maskedDestination);

        $event = $this->spy->lastEvent();
        $this->assertInstanceOf(SignatureOtpRequested::class, $event);
        $this->assertNotEmpty($event->otpChallenge()->plainCode());

        $updated = $repository->findByToken($signatureRequest->token()->hash());
        $this->assertNotNull($updated?->otpChallenge());
    }

    public function testReturnsAlreadyVerifiedWhenOtpIsVerified(): void
    {
        $repository = SignatureRequestRepositoryMother::inMemory();
        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $token = $signatureRequest->token();

        $challenge = OtpChallenge::generate(self::APP_SECRET);
        $signatureRequest->sendOtp($challenge);
        $signatureRequest->verifyOtp($challenge->plainCode(), self::APP_SECRET);
        $repository->add($signatureRequest);

        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();

        $sut = new SendOtpService(repository: $repository, appSecret: self::APP_SECRET);

        $response = $sut(new SendOtpRequest(tokenPlainValue: $token->plainValue()));

        $this->assertTrue($response->alreadyVerified);
        $this->assertNull($this->spy->lastEvent());
    }

    public function testDoesNotRegenerateOtpIfValidOneExists(): void
    {
        $repository = SignatureRequestRepositoryMother::inMemory();
        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $token = $signatureRequest->token();

        $challenge = OtpChallenge::generate(self::APP_SECRET);
        $signatureRequest->sendOtp($challenge);
        $repository->add($signatureRequest);

        $firstHash = $signatureRequest->otpChallenge()?->codeHash();

        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();

        $sut = new SendOtpService(repository: $repository, appSecret: self::APP_SECRET);
        $sut(new SendOtpRequest(tokenPlainValue: $token->plainValue()));

        $this->assertNull($this->spy->lastEvent(), 'No debería generarse un nuevo OTP si ya existe uno válido');

        $afterRequest = $repository->findByToken($signatureRequest->token()->hash());
        $this->assertEquals($firstHash, $afterRequest?->otpChallenge()?->codeHash());
    }

    public function testMasksEmailDestinationCorrectly(): void
    {
        $repository = SignatureRequestRepositoryMother::inMemory();
        $signatureRequest = SignatureRequestMother::from(
            id: '00000000-0000-0000-0000-000000000099',
            signerEmail: 'alberto@example.com',
        );
        $token = $signatureRequest->token();
        $repository->add($signatureRequest);

        $sut = new SendOtpService(repository: $repository, appSecret: self::APP_SECRET);
        $response = $sut(new SendOtpRequest(tokenPlainValue: $token->plainValue()));

        $this->assertStringStartsWith('a', $response->maskedDestination);
        $this->assertStringContainsString('*', $response->maskedDestination);
        $this->assertStringContainsString('@example.com', $response->maskedDestination);
    }

    public function testThrowsSignatureTokenInvalidExceptionForUnknownToken(): void
    {
        $this->expectException(SignatureTokenInvalidException::class);

        $repository = SignatureRequestRepositoryMother::inMemory();
        $sut = new SendOtpService(repository: $repository, appSecret: self::APP_SECRET);

        $sut(new SendOtpRequest(tokenPlainValue: 'nonexistent-token'));
    }

    public function testThrowsSignatureTokenInvalidExceptionForExpiredRequest(): void
    {
        $this->expectException(SignatureTokenInvalidException::class);

        $repository = SignatureRequestRepositoryMother::inMemory();
        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $signatureRequest->expire();
        $token = $signatureRequest->token();
        $repository->add($signatureRequest);

        $sut = new SendOtpService(repository: $repository, appSecret: self::APP_SECRET);
        $sut(new SendOtpRequest(tokenPlainValue: $token->plainValue()));
    }

    public function testThrowsSignatureTokenInvalidExceptionForRemovedRequest(): void
    {
        $this->expectException(SignatureTokenInvalidException::class);

        $repository = SignatureRequestRepositoryMother::inMemory();
        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $signatureRequest->remove();
        $token = $signatureRequest->token();
        $repository->add($signatureRequest);

        $sut = new SendOtpService(repository: $repository, appSecret: self::APP_SECRET);
        $sut(new SendOtpRequest(tokenPlainValue: $token->plainValue()));
    }

    public function testResponseContainsSignatureRequestId(): void
    {
        $repository = SignatureRequestRepositoryMother::inMemory();
        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $token = $signatureRequest->token();
        $repository->add($signatureRequest);

        $sut = new SendOtpService(repository: $repository, appSecret: self::APP_SECRET);
        $response = $sut(new SendOtpRequest(tokenPlainValue: $token->plainValue()));

        $this->assertTrue($signatureRequest->id()->equals($response->signatureRequestId));
    }
}
