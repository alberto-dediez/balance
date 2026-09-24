<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Security\TwoFactor;

use App\Security\Domain\Model\User\TotpAuthenticator;

final class InMemoryTotpAuthenticator implements TotpAuthenticator
{
    public const VALID_CODE = '123456';

    public const SECRET = 'in-memory-secret';

    public const UNREADABLE_SECRET = 'unreadable';

    public function generateSecret(): string
    {
        return self::SECRET;
    }

    public function provisioningUri(string $encryptedSecret, string $label, string $issuer): string
    {
        return 'otpauth://totp/test';
    }

    public function manualKey(string $encryptedSecret): string
    {
        return 'INMEMORYSECRET';
    }

    public function verify(string $encryptedSecret, string $code): bool
    {
        return self::VALID_CODE === $code && $this->isReadable($encryptedSecret);
    }

    public function isReadable(string $encryptedSecret): bool
    {
        return self::UNREADABLE_SECRET !== $encryptedSecret;
    }
}
