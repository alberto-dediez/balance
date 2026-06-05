<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

class Token implements \Stringable
{
    private const HKDF_INFO = 'signature-token';
    private const HKDF_LENGTH = 32;

    public function __construct(
        private readonly string $hash,
        #[\SensitiveParameter]
        private readonly string $plainValue = '',
    ) {}

    public static function generate(#[\SensitiveParameter] string $appSecret): self
    {
        $plainValue = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $hash = self::computeHash($plainValue, $appSecret);

        return new self(hash: $hash, plainValue: $plainValue);
    }

    public function verify(#[\SensitiveParameter] string $candidatePlainValue, #[\SensitiveParameter] string $appSecret): bool
    {
        return hash_equals($this->hash, self::computeHash($candidatePlainValue, $appSecret));
    }

    public function hash(): string
    {
        return $this->hash;
    }

    public function plainValue(): string
    {
        return $this->plainValue;
    }

    public function __toString(): string
    {
        return $this->hash;
    }

    public static function hashFor(#[\SensitiveParameter] string $plainValue, #[\SensitiveParameter] string $appSecret): string
    {
        return self::computeHash($plainValue, $appSecret);
    }

    private static function computeHash(#[\SensitiveParameter] string $plainValue, #[\SensitiveParameter] string $appSecret): string
    {
        $derivedKey = hash_hkdf('sha256', $appSecret, self::HKDF_LENGTH, self::HKDF_INFO);

        return hash_hmac('sha256', $plainValue, $derivedKey);
    }
}
