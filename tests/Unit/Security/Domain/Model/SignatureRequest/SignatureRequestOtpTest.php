<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\SignatureRequest;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\SignatureRequest\DocumentReference;
use App\Security\Domain\Model\SignatureRequest\NotificationChannel;
use App\Security\Domain\Model\SignatureRequest\OtpChallenge;
use App\Security\Domain\Model\SignatureRequest\SignatureOtpRequested;
use App\Security\Domain\Model\SignatureRequest\SignatureOtpVerified;
use App\Security\Domain\Model\SignatureRequest\SignatureRequest;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Security\Domain\Model\SignatureRequest\Signer;
use App\Security\Domain\Model\SignatureRequest\Token;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class SignatureRequestOtpTest extends TestCase
{
    private const APP_SECRET = 'test-app-secret';

    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();
    }

    public function testSendOtpPublishesSignatureOtpRequestedEvent(): void
    {
        $signatureRequest = $this->makeRequestWithSmsChannel();
        $challenge = OtpChallenge::generate(self::APP_SECRET);

        $signatureRequest->sendOtp($challenge);

        $event = $this->spy->lastEvent();
        $this->assertInstanceOf(SignatureOtpRequested::class, $event);
        $this->assertEquals(NotificationChannel::Sms, $event->notificationChannel());
        $this->assertNotEmpty($event->maskedDestination());
        $this->assertNotEmpty($event->otpChallenge()->plainCode());
    }

    public function testSendOtpStoresChallengeOnAggregate(): void
    {
        $signatureRequest = $this->makeRequestWithSmsChannel();
        $challenge = OtpChallenge::generate(self::APP_SECRET);

        $this->assertNull($signatureRequest->otpChallenge());

        $signatureRequest->sendOtp($challenge);

        $this->assertNotNull($signatureRequest->otpChallenge());
        $this->assertFalse($signatureRequest->isOtpVerified());
    }

    public function testSendOtpMasksEmailDestination(): void
    {
        $signatureRequest = SignatureRequestMother::from(
            id: (string) SignatureRequestId::generate(),
            signerEmail: 'alberto@example.com',
        );
        $challenge = OtpChallenge::generate(self::APP_SECRET);
        $signatureRequest->sendOtp($challenge);

        $event = $this->spy->lastEvent();
        $this->assertInstanceOf(SignatureOtpRequested::class, $event);
        $this->assertStringContainsString('*', $event->maskedDestination());
        $this->assertStringContainsString('@example.com', $event->maskedDestination());
    }

    public function testSendOtpMasksPhoneDestination(): void
    {
        $signatureRequest = $this->makeRequestWithSmsChannel('+34666123456');
        $challenge = OtpChallenge::generate(self::APP_SECRET);
        $signatureRequest->sendOtp($challenge);

        $event = $this->spy->lastEvent();
        $this->assertInstanceOf(SignatureOtpRequested::class, $event);
        $this->assertStringEndsWith('456', $event->maskedDestination());
        $this->assertStringContainsString('*', $event->maskedDestination());
    }

    public function testVerifyOtpReturnsTrueAndPublishesVerifiedEvent(): void
    {
        $signatureRequest = $this->makeRequestWithSmsChannel();
        $challenge = OtpChallenge::generate(self::APP_SECRET);
        $signatureRequest->sendOtp($challenge);

        $result = $signatureRequest->verifyOtp($challenge->plainCode(), self::APP_SECRET);

        $this->assertTrue($result);
        $this->assertTrue($signatureRequest->isOtpVerified());

        $event = $this->spy->lastEvent();
        $this->assertInstanceOf(SignatureOtpVerified::class, $event);
    }

    public function testVerifyOtpReturnsFalseAndIncreasesAttemptsForWrongCode(): void
    {
        $signatureRequest = $this->makeRequestWithSmsChannel();
        $challenge = OtpChallenge::generate(self::APP_SECRET);
        $signatureRequest->sendOtp($challenge);

        $wrongCode = '111111' === $challenge->plainCode() ? '222222' : '111111';
        $result = $signatureRequest->verifyOtp($wrongCode, self::APP_SECRET);

        $this->assertFalse($result);
        $this->assertFalse($signatureRequest->isOtpVerified());
        $this->assertEquals(1, $signatureRequest->otpChallenge()?->attempts());

        $events = $this->spy->events();
        $lastEvent = end($events);
        $this->assertNotInstanceOf(SignatureOtpVerified::class, $lastEvent);
    }

    public function testVerifyOtpReturnsFalseWhenNoChallengeExists(): void
    {
        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();

        $result = $signatureRequest->verifyOtp('123456', self::APP_SECRET);

        $this->assertFalse($result);
    }

    public function testIsOtpVerifiedReturnsFalseInitially(): void
    {
        $signatureRequest = SignatureRequestMother::registeredSignatureRequest();

        $this->assertFalse($signatureRequest->isOtpVerified());
        $this->assertNull($signatureRequest->otpChallenge());
    }

    public function testSignatureOtpRequestedPayloadDoesNotContainPlainCode(): void
    {
        $signatureRequest = $this->makeRequestWithSmsChannel();
        $challenge = OtpChallenge::generate(self::APP_SECRET);
        $signatureRequest->sendOtp($challenge);

        $event = $this->spy->lastEvent();
        $this->assertInstanceOf(SignatureOtpRequested::class, $event);

        $payload = $event->payload();

        $this->assertArrayNotHasKey('plainCode', $payload);
        $this->assertArrayNotHasKey('codeHash', $payload);
        $this->assertArrayHasKey('signatureRequestId', $payload);
        $this->assertArrayHasKey('notificationChannel', $payload);
        $this->assertArrayHasKey('maskedDestination', $payload);
        $this->assertArrayHasKey('expiresAt', $payload);
    }

    public function testSignatureOtpVerifiedPayload(): void
    {
        $signatureRequest = $this->makeRequestWithSmsChannel();
        $challenge = OtpChallenge::generate(self::APP_SECRET);
        $signatureRequest->sendOtp($challenge);
        $signatureRequest->verifyOtp($challenge->plainCode(), self::APP_SECRET, '127.0.0.1');

        $verifiedEvent = null;
        foreach (array_reverse($this->spy->events()) as $event) {
            if ($event instanceof SignatureOtpVerified) {
                $verifiedEvent = $event;
                break;
            }
        }

        $this->assertNotNull($verifiedEvent);

        $payload = $verifiedEvent->payload();

        $this->assertArrayHasKey('signatureRequestId', $payload);
        $this->assertArrayHasKey('verifiedAt', $payload);
        $this->assertArrayHasKey('signerIp', $payload);
        $this->assertEquals('127.0.0.1', $payload['signerIp']);
    }

    private function makeRequestWithSmsChannel(string $phone = '+34600000000'): SignatureRequest
    {
        $signatureRequest = new SignatureRequest(id: SignatureRequestId::generate());
        $signatureRequest->register(
            token: Token::generate(self::APP_SECRET),
            documentToSign: new DocumentReference(
                sha256: hash('sha256', 'test'),
                size: 100,
                filename: 'test.pdf',
                path: '/tmp/test.pdf',
            ),
            signer: new Signer(
                name: 'Firmante SMS',
                locale: Locale::Spanish,
                email: 'signer@example.com',
                phone: $phone,
                notificationChannel: NotificationChannel::Sms,
            ),
            accountId: new AccountId('test-account'),
        );

        return $signatureRequest;
    }
}
