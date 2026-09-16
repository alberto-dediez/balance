<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Sso\Bioscore\InMemory;

use App\Security\Infrastructure\Sso\Bioscore\BioscoreJwtDecoder;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
final class InMemoryBioscoreJwtDecoder implements BioscoreJwtDecoder
{
    private ?string $email = null;

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function reset(): void
    {
        $this->email = null;
    }

    public function decode(string $token): array
    {
        return null === $this->email ? [] : ['email' => $this->email];
    }
}
