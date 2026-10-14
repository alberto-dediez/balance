<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Sso\Bioscore\InMemory;

use App\Security\Infrastructure\Sso\Bioscore\BioscoreClient;
use App\Shared\Domain\Model\Locale\Locale;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
final class InMemoryBioscoreClient implements BioscoreClient
{
    public function __construct(
        private bool $validToken = true,
    ) {}

    public function setValidToken(bool $validToken): self
    {
        $this->validToken = $validToken;

        return $this;
    }

    public function reset(): void
    {
        $this->validToken = true;
    }

    public function loginUrl(Locale $locale): string
    {
        return sprintf('https://sso.test/%s/login-sso/bioscore', $locale->value);
    }

    public function checkToken(string $token): bool
    {
        return $this->validToken;
    }
}
