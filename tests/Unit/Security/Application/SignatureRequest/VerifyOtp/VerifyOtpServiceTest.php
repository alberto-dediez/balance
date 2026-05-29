<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\SignatureRequest\VerifyOtp;

use App\Security\Application\SignatureRequest\VerifyOtp\VerifyOtpRequest;
use App\Security\Application\SignatureRequest\VerifyOtp\VerifyOtpService;
use App\Security\Domain\Model\SignatureRequest\OtpChallenge;
use App\Security\Domain\Model\SignatureRequest\SignatureOtpVerified;
use App\Security\Domain\Model\SignatureRequest\SignatureTokenInvalidException;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestMother;
use App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestRepositoryMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class VerifyOtpServiceTest extends TestCase
{
    private const APP_SECRET = 'test-app-secret';

    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();
    }

    public function testVerifiesCorrectCodeAndPublishesEvent(): void
    {
        $repository = SignatureRequestRepositoryMother::inMemory();
        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $token = $signatureRequest->token();

        $challenge = OtpChallenge::generate(self::APP_SECRET);
        $signatureRequest->sendOtp($challenge);
        $repository->add($signatureRequest);

        $sut = new VerifyOtpService(repository: $repository, appSecret: self::APP_SECRET);

        $response = $sut(new VerifyOtpRequest(
            tokenPlainValue: $token->plainValue(),
            otpCode: $challenge->plainCode(),
            signerIp: '192.168.1.1',
        ));

        $this->assertTrue($response->verified);
        $this->assertFalse($response->locked);
        $this->assertEquals(3, $response->attemptsLeft);

        $verifiedEvent = null;
        foreach (array_reverse($this->spy->events()) as $event) {
            if ($event instanceof SignatureOtpVerified) {
                $verifiedEvent = $event;
                break;
            }
        }
        $this->assertNotNull($verifiedEvent);
        $this->assertEquals('192.168.1.1', $verifiedEvent->signerIp());

        $updated = $repository->findByToken($signatureRequest->token()->hash());
        $this->assertTrue($updated?->isOtpVerified() ?? false);
    }

    public function testRejectsWrongCodeAndDecrementsAttempts(): void
    {
        $repository = SignatureRequestRepositoryMother::inMemory();
        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $token = $signatureRequest->token();

        $challenge = OtpChallenge::generate(self::APP_SECRET);
        $signatureRequest->sendOtp($challenge);
        $repository->add($signatureRequest);

        $sut = new VerifyOtpService(repository: $repository, appSecret: self::APP_SECRET);

        $wrongCode = '111111' === $challenge->plainCode() ? '222222' : '111111';
        $response = $sut(new VerifyOtpRequest(
            tokenPlainValue: $token->plainValue(),
            otpCode: $wrongCode,
        ));

        $this->assertFalse($response->verified);
        $this->assertFalse($response->locked);
        $this->assertEquals(2, $response->attemptsLeft);

        $updated = $repository->findByToken($signatureRequest->token()->hash());
        $this->assertEquals(1, $updated?->otpChallenge()?->attempts());
    }

    public function testLocksAfterThreeWrongAttempts(): void
    {
        $repository = SignatureRequestRepositoryMother::inMemory();
        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $token = $signatureRequest->token();

        $challenge = OtpChallenge::generate(self::APP_SECRET);
        $signatureRequest->sendOtp($challenge);
        $repository->add($signatureRequest);

        $sut = new VerifyOtpService(repository: $repository, appSecret: self::APP_SECRET);
        $wrongCode = '111111' === $challenge->plainCode() ? '222222' : '111111';

        $sut(new VerifyOtpRequest(tokenPlainValue: $token->plainValue(), otpCode: $wrongCode));
        $sut(new VerifyOtpRequest(tokenPlainValue: $token->plainValue(), otpCode: $wrongCode));
        $response = $sut(new VerifyOtpRequest(tokenPlainValue: $token->plainValue(), otpCode: $wrongCode));

        $this->assertFalse($response->verified);
        $this->assertTrue($response->locked);
        $this->assertEquals(0, $response->attemptsLeft);
    }

    public function testThrowsSignatureTokenInvalidExceptionForUnknownToken(): void
    {
        $this->expectException(SignatureTokenInvalidException::class);

        $repository = SignatureRequestRepositoryMother::inMemory();
        $sut = new VerifyOtpService(repository: $repository, appSecret: self::APP_SECRET);

        $sut(new VerifyOtpRequest(tokenPlainValue: 'nonexistent-token', otpCode: '123456'));
    }

    public function testThrowsSignatureTokenInvalidExceptionForExpiredRequest(): void
    {
        $this->expectException(SignatureTokenInvalidException::class);

        $repository = SignatureRequestRepositoryMother::inMemory();
        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();
        $signatureRequest->expire();
        $token = $signatureRequest->token();
        $repository->add($signatureRequest);

        $sut = new VerifyOtpService(repository: $repository, appSecret: self::APP_SECRET);
        $sut(new VerifyOtpRequest(tokenPlainValue: $token->plainValue(), otpCode: '123456'));
    }

    public function testResponseIncludesNotificationChannelAndMaskedDestination(): void
    {
        $repository = SignatureRequestRepositoryMother::inMemory();
        $signatureRequest = SignatureRequestMother::from(
            id: '00000000-0000-0000-0000-000000000099',
            signerEmail: 'signer@example.com',
        );
        $token = $signatureRequest->token();

        $challenge = OtpChallenge::generate(self::APP_SECRET);
        $signatureRequest->sendOtp($challenge);
        $repository->add($signatureRequest);

        $sut = new VerifyOtpService(repository: $repository, appSecret: self::APP_SECRET);
        $wrongCode = '111111' === $challenge->plainCode() ? '222222' : '111111';
        $response = $sut(new VerifyOtpRequest(tokenPlainValue: $token->plainValue(), otpCode: $wrongCode));

        $this->assertNotEmpty($response->maskedDestination);
        $this->assertStringContainsString('@example.com', $response->maskedDestination);
    }
}
