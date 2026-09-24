<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\User;

interface TotpAuthenticator
{
    public function generateSecret(): string;

    public function provisioningUri(string $encryptedSecret, string $label, string $issuer): string;

    public function manualKey(string $encryptedSecret): string;

    public function verify(string $encryptedSecret, string $code): bool;

    public function isReadable(string $encryptedSecret): bool;
}
