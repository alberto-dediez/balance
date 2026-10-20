<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Webhook;

use App\Security\Infrastructure\Webhook\SendgridSignatureVerifier;
use PHPUnit\Framework\TestCase;

class SendgridSignatureVerifierTest extends TestCase
{
    private const string PAYLOAD = '[{"email":"recipient@example.com","timestamp":1700000000,"event":"delivered","sg_event_id":"e1"}]';

    private const string TIMESTAMP = '1700000000';

    private const string SMTP_DSN = 'smtp://mailer:1025';

    private const string SENDGRID_DSN = 'sendgrid://KEY@default';

    private \OpenSSLAsymmetricKey $privateKey;

    private string $publicKey;

    protected function setUp(): void
    {
        $privateKey = openssl_pkey_new(['private_key_type' => \OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        $this->assertNotFalse($privateKey);
        $this->privateKey = $privateKey;

        $details = openssl_pkey_get_details($privateKey);
        $this->assertNotFalse($details);

        $this->publicKey = str_replace(
            ['-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----', "\n"],
            '',
            (string) $details['key'],
        );
    }

    public function testValidSignatureIsAccepted(): void
    {
        $sut = new SendgridSignatureVerifier($this->publicKey, self::SENDGRID_DSN);

        $this->assertTrue($sut->verify($this->sign(self::TIMESTAMP . self::PAYLOAD), self::TIMESTAMP, self::PAYLOAD));
    }

    public function testSignatureOfAnotherPayloadIsRejected(): void
    {
        $sut = new SendgridSignatureVerifier($this->publicKey, self::SENDGRID_DSN);

        $this->assertFalse($sut->verify($this->sign(self::TIMESTAMP . '[]'), self::TIMESTAMP, self::PAYLOAD));
    }

    public function testSignatureWithAnotherTimestampIsRejected(): void
    {
        $sut = new SendgridSignatureVerifier($this->publicKey, self::SENDGRID_DSN);

        $this->assertFalse($sut->verify($this->sign(self::TIMESTAMP . self::PAYLOAD), '1700000001', self::PAYLOAD));
    }

    public function testMissingHeadersAreRejected(): void
    {
        $sut = new SendgridSignatureVerifier($this->publicKey, self::SENDGRID_DSN);

        $this->assertFalse($sut->verify(null, null, self::PAYLOAD));
        $this->assertFalse($sut->verify('', self::TIMESTAMP, self::PAYLOAD));
    }

    public function testWrongPublicKeyIsRejected(): void
    {
        $sut = new SendgridSignatureVerifier('not-a-key', self::SENDGRID_DSN);

        $this->assertFalse($sut->verify($this->sign(self::TIMESTAMP . self::PAYLOAD), self::TIMESTAMP, self::PAYLOAD));
    }

    public function testEmptySecretSkipsVerificationWhenProviderIsNotSendgrid(): void
    {
        $sut = new SendgridSignatureVerifier('', self::SMTP_DSN);

        $this->assertFalse($sut->isSecretMissing());
        $this->assertTrue($sut->verify(null, null, self::PAYLOAD));
    }

    public function testEmptySecretIsRejectedWhenProviderIsSendgrid(): void
    {
        $sut = new SendgridSignatureVerifier('', self::SENDGRID_DSN);

        $this->assertTrue($sut->isSecretMissing());
        $this->assertFalse($sut->verify(null, null, self::PAYLOAD));
        $this->assertFalse($sut->verify($this->sign(self::TIMESTAMP . self::PAYLOAD), self::TIMESTAMP, self::PAYLOAD));
    }

    public function testEmptySecretIsRejectedWhenProviderIsSendgridApi(): void
    {
        $sut = new SendgridSignatureVerifier('', 'sendgrid+api://KEY@default');

        $this->assertFalse($sut->verify(null, null, self::PAYLOAD));
    }

    private function sign(string $data): string
    {
        $this->assertTrue(openssl_sign($data, $signature, $this->privateKey, \OPENSSL_ALGO_SHA256));

        return base64_encode((string) $signature);
    }
}
