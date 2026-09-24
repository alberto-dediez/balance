<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Symfony\Security\TwoFactor;

use App\Security\Infrastructure\Symfony\Security\TwoFactor\OtphpTotpAuthenticator;
use OTPHP\TOTP;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\NativeClock;

class OtphpTotpAuthenticatorTest extends TestCase
{
    public function testGeneratedSecretIsEncrypted(): void
    {
        $sut = new OtphpTotpAuthenticator('app-secret');

        $secret = $sut->generateSecret();

        $this->assertNotEmpty($sut->manualKey($secret));
        $this->assertNotEquals($sut->manualKey($secret), $secret);
        $this->assertTrue($sut->isReadable($secret));
    }

    public function testVerifyAcceptsTheCurrentCodeAndRejectsAnother(): void
    {
        $sut = new OtphpTotpAuthenticator('app-secret');
        $secret = $sut->generateSecret();

        $manualKey = $sut->manualKey($secret);

        if ('' === $manualKey) {
            $this->fail('The generated secret must be readable');
        }

        $code = TOTP::createFromSecret($manualKey, new NativeClock())->now();
        $wrongCode = str_pad((string) (((int) $code + 500000) % 1000000), 6, '0', STR_PAD_LEFT);

        $this->assertTrue($sut->verify($secret, $code));
        $this->assertFalse($sut->verify($secret, $wrongCode));
    }

    public function testASecretEncryptedWithAnotherAppSecretIsNotReadable(): void
    {
        $secret = (new OtphpTotpAuthenticator('old-app-secret'))->generateSecret();

        $sut = new OtphpTotpAuthenticator('new-app-secret');

        $this->assertFalse($sut->isReadable($secret));
        $this->assertFalse($sut->verify($secret, '123456'));
        $this->assertSame('', $sut->manualKey($secret));
        $this->assertFalse($sut->isReadable('not-base64-!!'));
    }

    public function testProvisioningUriContainsIssuerAndLabel(): void
    {
        $sut = new OtphpTotpAuthenticator('app-secret');
        $secret = $sut->generateSecret();

        $uri = $sut->provisioningUri($secret, 'user@dediez.software', 'Hydra');

        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('issuer=Hydra', $uri);
        $this->assertStringContainsString(rawurlencode('user@dediez.software'), $uri);
        $this->assertStringContainsString('secret=' . $sut->manualKey($secret), $uri);
    }
}
