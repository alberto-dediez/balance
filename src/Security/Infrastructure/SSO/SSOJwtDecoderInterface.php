<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\SSO;

interface SSOJwtDecoderInterface
{
    /**
     * @return array<string, mixed>
     *
     * @throws \Exception If the token is invalid
     */
    public function decode(string $tokenizeData): array;
}
