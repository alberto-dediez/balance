<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\SSO;

use App\Security\Infrastructure\SSO\SSOJwtDecoder;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Builder;
use PHPUnit\Framework\TestCase;

class SSOJwtDecoderTest extends TestCase
{
    private string $privateKey;
    private string $publicKey;

    protected function setUp(): void
    {
        $res = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        self::assertNotFalse($res);
        openssl_pkey_export($res, $privateKey);
        $this->privateKey = $privateKey;

        $details = openssl_pkey_get_details($res);
        self::assertNotFalse($details);
        $this->publicKey = $details['key'];
    }

    public function testDecodeValidToken(): void
    {
        $decoder = new SSOJwtDecoder($this->publicKey);
        $token = $this->createToken(['email' => 'test@example.com']);

        $payload = $decoder->decode($token);

        $this->assertEquals('test@example.com', $payload['email']);
    }

    public function testDecodeInvalidSignature(): void
    {
        // Generate another key pair for an invalid signature
        $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        self::assertNotFalse($res);
        openssl_pkey_export($res, $otherPrivateKey);

        $decoder = new SSOJwtDecoder($this->publicKey);
        $token = $this->createToken(['sub' => 'user-123'], $otherPrivateKey);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid JWT signature.');

        $decoder->decode($token);
    }

    public function testDecodeExpiredToken(): void
    {
        $decoder = new SSOJwtDecoder($this->publicKey);
        $token = $this->createToken(['sub' => 'user-123'], $this->privateKey, (new \DateTimeImmutable())->modify('-1 hour'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Expired or future JWT token (clock skew).');

        $decoder->decode($token);
    }

    public function testDecodeMalformedToken(): void
    {
        $decoder = new SSOJwtDecoder($this->publicKey);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to parse JWT token.');

        $decoder->decode('not-a-jwt');
    }

    /** @param array<string, mixed> $claims */
    private function createToken(array $claims, ?string $privateKey = null, ?\DateTimeImmutable $expiresAt = null): string
    {
        $tokenBuilder = clone new Builder(new JoseEncoder(), ChainedFormatter::default());
        $algorithm    = new Sha256();
        $keyContents  = $privateKey ?? $this->privateKey;
        self::assertNotEmpty($keyContents);
        $signingKey   = InMemory::plainText($keyContents);

        $now = new \DateTimeImmutable();
        $expiresAt = $expiresAt ?? $now->modify('+1 hour');

        $tokenBuilder = $tokenBuilder
            ->issuedBy('https://example.com')
            ->issuedAt($now)
            ->expiresAt($expiresAt);

        foreach ($claims as $name => $value) {
            self::assertNotEmpty($name);
            $tokenBuilder = match ($name) {
                'sub' => $tokenBuilder->relatedTo($value),
                'aud' => $tokenBuilder->permittedFor($value),
                'jti' => $tokenBuilder->identifiedBy($value),
                default => $tokenBuilder->withClaim($name, $value),
            };
        }

        return $tokenBuilder->getToken($algorithm, $signingKey)->toString();
    }
}
