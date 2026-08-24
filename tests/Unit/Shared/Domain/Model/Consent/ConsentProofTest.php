<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model\Consent;

use App\Shared\Domain\Model\Consent\ConsentProof;
use PHPUnit\Framework\TestCase;

class ConsentProofTest extends TestCase
{
    public function testKeepsTheProvidedData(): void
    {
        $consentProof = ConsentProofMother::granted();

        $this->assertSame(ConsentProofMother::CONSENTED_AT, $consentProof->consentedAt()?->format('Y-m-d H:i:s'));
        $this->assertSame(ConsentProofMother::POLICY_VERSION, $consentProof->policyVersion());
        $this->assertSame(ConsentProofMother::LOCALE, $consentProof->locale());
        $this->assertSame(ConsentProofMother::IP, $consentProof->ip());
        $this->assertSame('Mozilla/5.0', $consentProof->userAgent());
        $this->assertSame('es-ES,es;q=0.9', $consentProof->acceptLanguage());
    }

    public function testFailsWithoutConsentedAt(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ConsentProof(
            consentedAt: null,
            policyVersion: ConsentProofMother::POLICY_VERSION,
            locale: ConsentProofMother::LOCALE,
        );
    }

    public function testFailsWithoutPolicyVersion(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ConsentProofMother::granted(policyVersion: '  ');
    }

    public function testFailsWithoutLocale(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ConsentProofMother::granted(locale: '');
    }

    public function testFailsWithAnOversizedPolicyVersion(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ConsentProofMother::granted(policyVersion: str_repeat('v', ConsentProof::POLICY_VERSION_MAX_LENGTH + 1));
    }

    public function testFailsWithAnOversizedLocale(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ConsentProofMother::granted(locale: str_repeat('l', ConsentProof::LOCALE_MAX_LENGTH + 1));
    }

    public function testFailsWithAnOversizedIp(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ConsentProofMother::granted(ip: str_repeat('a', ConsentProof::IP_MAX_LENGTH + 1));
    }

    public function testNormalisesEmptyOptionalValuesToNull(): void
    {
        $consentProof = ConsentProofMother::granted(ip: '', userAgent: '   ', acceptLanguage: null);

        $this->assertNull($consentProof->ip());
        $this->assertNull($consentProof->userAgent());
        $this->assertNull($consentProof->acceptLanguage());
    }

    public function testTruncatesTheUserAgentToItsMaximumLength(): void
    {
        $consentProof = ConsentProofMother::granted(userAgent: str_repeat('u', ConsentProof::USER_AGENT_MAX_LENGTH + 10));

        $this->assertSame(ConsentProof::USER_AGENT_MAX_LENGTH, mb_strlen((string) $consentProof->userAgent()));
    }

    public function testTruncatesTheAcceptLanguageToItsMaximumLength(): void
    {
        $consentProof = ConsentProofMother::granted(acceptLanguage: str_repeat('l', ConsentProof::ACCEPT_LANGUAGE_MAX_LENGTH + 10));

        $this->assertSame(ConsentProof::ACCEPT_LANGUAGE_MAX_LENGTH, mb_strlen((string) $consentProof->acceptLanguage()));
    }
}
