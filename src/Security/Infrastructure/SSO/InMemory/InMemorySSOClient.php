<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\SSO\InMemory;

use App\Security\Infrastructure\SSO\SSOClient;
use App\Shared\Domain\Model\Locale\Locale;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemorySSOClient implements SSOClient
{
    public function __construct(
        private string $ssoUrl = '',
        private bool $validToken = true,
    ) {}

    public function setValidToken(bool $validToken): self
    {
        $this->validToken = $validToken;

        return $this;
    }

    public function getLoginUrl(Locale $locale = Locale::Spanish): string
    {
        return $this->ssoUrl;
    }

    public function checkToken(string $token): bool
    {
        return $this->validToken;
    }
}
