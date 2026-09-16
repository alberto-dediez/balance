<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Sso\OAuth2;

use App\Security\Infrastructure\Sso\SsoAuthenticationFailedException;
use App\Security\Infrastructure\Sso\SsoIdentity;
use App\Security\Infrastructure\Sso\SsoProvider;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpFoundation\Request;

#[When(env: 'dev')]
#[When(env: 'prod')]
final readonly class GoogleSsoProvider implements SsoProvider
{
    private const KEY = 'google';

    public function __construct(
        private ClientRegistry $clientRegistry,
        private bool $enabled,
    ) {}

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'Google';
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function loginUrl(): string
    {
        return $this->clientRegistry->getClient(self::KEY)->redirect(['openid', 'email', 'profile'], [])->getTargetUrl();
    }

    public function authenticate(Request $request): SsoIdentity
    {
        try {
            $user = $this->clientRegistry->getClient(self::KEY)->fetchUser();
        } catch (\Throwable $e) {
            throw SsoAuthenticationFailedException::fromProvider(self::KEY, $e);
        }

        $claims = $user->toArray();

        if (true !== ($claims['email_verified'] ?? false)) {
            throw SsoAuthenticationFailedException::unverifiedEmail(self::KEY);
        }

        $email = $claims['email'] ?? null;

        if (!is_string($email) || '' === $email) {
            throw SsoAuthenticationFailedException::missingEmail(self::KEY);
        }

        $name = $claims['name'] ?? null;

        return new SsoIdentity(mb_strtolower($email), is_string($name) ? $name : null);
    }
}
