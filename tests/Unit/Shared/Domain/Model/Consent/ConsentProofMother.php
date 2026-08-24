<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model\Consent;

use App\Shared\Domain\Model\Consent\ConsentProof;

class ConsentProofMother
{
    public const string CONSENTED_AT = '2026-08-24 10:00:00';
    public const string POLICY_VERSION = '2026-08-24';
    public const string LOCALE = 'es';
    public const string IP = '127.0.0.1';

    public static function granted(
        string $consentedAt = self::CONSENTED_AT,
        string $policyVersion = self::POLICY_VERSION,
        string $locale = self::LOCALE,
        ?string $ip = self::IP,
        ?string $userAgent = 'Mozilla/5.0',
        ?string $acceptLanguage = 'es-ES,es;q=0.9',
    ): ConsentProof {
        return new ConsentProof(
            consentedAt: new \DateTimeImmutable($consentedAt),
            policyVersion: $policyVersion,
            locale: $locale,
            ip: $ip,
            userAgent: $userAgent,
            acceptLanguage: $acceptLanguage,
        );
    }
}
