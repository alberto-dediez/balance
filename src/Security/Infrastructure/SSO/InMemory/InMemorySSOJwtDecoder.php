<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\SSO\InMemory;

use App\Security\Infrastructure\SSO\SSOJwtDecoderInterface;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemorySSOJwtDecoder implements SSOJwtDecoderInterface
{
    private string $email = 'user@dediez.software';

    public function __construct()
    {
        // no public key needed in tests
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function decode(string $tokenizeData): array
    {
        return ['email' => $this->email];
    }
}
