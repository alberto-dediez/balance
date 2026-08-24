<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Consent;

class ConsentProof
{
    public const int POLICY_VERSION_MAX_LENGTH = 64;
    public const int LOCALE_MAX_LENGTH = 5;
    public const int IP_MAX_LENGTH = 45;
    public const int USER_AGENT_MAX_LENGTH = 512;
    public const int ACCEPT_LANGUAGE_MAX_LENGTH = 255;

    public function __construct(
        private ?\DateTimeImmutable $consentedAt,
        private ?string $policyVersion,
        private ?string $locale,
        private ?string $ip = null,
        private ?string $userAgent = null,
        private ?string $acceptLanguage = null,
    ) {
        if (null === $this->consentedAt) {
            throw new \InvalidArgumentException('La fecha del consentimiento es obligatoria.');
        }

        if ('' === trim((string) $this->policyVersion)) {
            throw new \InvalidArgumentException('La versión de la cláusula de consentimiento es obligatoria.');
        }

        if (mb_strlen(trim((string) $this->policyVersion)) > self::POLICY_VERSION_MAX_LENGTH) {
            throw new \InvalidArgumentException('La versión de la cláusula de consentimiento excede la longitud máxima.');
        }

        if ('' === trim((string) $this->locale)) {
            throw new \InvalidArgumentException('El idioma del consentimiento es obligatorio.');
        }

        if (mb_strlen(trim((string) $this->locale)) > self::LOCALE_MAX_LENGTH) {
            throw new \InvalidArgumentException('El idioma del consentimiento excede la longitud máxima.');
        }

        if (null !== $this->ip && mb_strlen(trim($this->ip)) > self::IP_MAX_LENGTH) {
            throw new \InvalidArgumentException('La IP del consentimiento excede la longitud máxima.');
        }

        $this->ip = self::normalise($this->ip, self::IP_MAX_LENGTH);
        $this->userAgent = self::normalise($this->userAgent, self::USER_AGENT_MAX_LENGTH);
        $this->acceptLanguage = self::normalise($this->acceptLanguage, self::ACCEPT_LANGUAGE_MAX_LENGTH);
    }

    public function consentedAt(): ?\DateTimeImmutable
    {
        return $this->consentedAt;
    }

    public function policyVersion(): ?string
    {
        return $this->policyVersion;
    }

    public function locale(): ?string
    {
        return $this->locale;
    }

    public function ip(): ?string
    {
        return $this->ip;
    }

    public function userAgent(): ?string
    {
        return $this->userAgent;
    }

    public function acceptLanguage(): ?string
    {
        return $this->acceptLanguage;
    }

    private static function normalise(?string $value, int $maxLength): ?string
    {
        $value = null !== $value ? trim($value) : '';

        if ('' === $value) {
            return null;
        }

        return mb_substr($value, 0, $maxLength);
    }
}
