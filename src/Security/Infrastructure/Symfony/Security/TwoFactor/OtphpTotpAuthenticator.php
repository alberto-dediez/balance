<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security\TwoFactor;

use App\Security\Domain\Model\User\TotpAuthenticator;
use OTPHP\TOTP;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\NativeClock;

final class OtphpTotpAuthenticator implements TotpAuthenticator
{
    private const HKDF_INFO = 'two-factor-totp';
    private const LEEWAY_SECONDS = 29;

    public function __construct(
        #[\SensitiveParameter]
        private readonly string $appSecret,
        private readonly ClockInterface $clock = new NativeClock(),
    ) {}

    public function generateSecret(): string
    {
        return $this->encrypt(TOTP::generate($this->clock)->getSecret());
    }

    public function provisioningUri(string $encryptedSecret, string $label, string $issuer): string
    {
        $totp = $this->totp($encryptedSecret);

        if (!$totp instanceof TOTP) {
            return '';
        }

        if ('' !== $label) {
            $totp->setLabel($label);
        }

        if ('' !== $issuer) {
            $totp->setIssuer($issuer);
        }

        return $totp->getProvisioningUri();
    }

    public function manualKey(string $encryptedSecret): string
    {
        return $this->decrypt($encryptedSecret) ?? '';
    }

    public function verify(string $encryptedSecret, string $code): bool
    {
        $totp = $this->totp($encryptedSecret);

        if (!$totp instanceof TOTP || '' === $code) {
            return false;
        }

        return $totp->verify($code, null, self::LEEWAY_SECONDS);
    }

    public function isReadable(string $encryptedSecret): bool
    {
        return null !== $this->decrypt($encryptedSecret);
    }

    private function totp(string $encryptedSecret): ?TOTP
    {
        $secret = $this->decrypt($encryptedSecret);

        if (null === $secret) {
            return null;
        }

        return TOTP::createFromSecret($secret, $this->clock);
    }

    private function encrypt(#[\SensitiveParameter] string $secret): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return base64_encode($nonce . sodium_crypto_secretbox($secret, $nonce, $this->key()));
    }

    /**
     * @return non-empty-string|null
     */
    private function decrypt(string $encryptedSecret): ?string
    {
        $decoded = base64_decode($encryptedSecret, true);

        if (false === $decoded || strlen($decoded) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES) {
            return null;
        }

        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $secret = sodium_crypto_secretbox_open($cipher, $nonce, $this->key());

        return false === $secret || '' === $secret ? null : $secret;
    }

    private function key(): string
    {
        return hash_hkdf('sha256', $this->appSecret, SODIUM_CRYPTO_SECRETBOX_KEYBYTES, self::HKDF_INFO);
    }
}
