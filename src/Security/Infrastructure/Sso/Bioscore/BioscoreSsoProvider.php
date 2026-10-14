<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Sso\Bioscore;

use App\Security\Infrastructure\Sso\SsoAuthenticationFailedException;
use App\Security\Infrastructure\Sso\SsoIdentity;
use App\Security\Infrastructure\Sso\SsoProvider;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class BioscoreSsoProvider implements SsoProvider
{
    private const KEY = 'bioscore';

    public function __construct(
        private BioscoreClient $client,
        private BioscoreJwtDecoder $jwtDecoder,
        private RequestStack $requestStack,
        private bool $enabled,
    ) {}

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'Bioscore';
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function loginUrl(): string
    {
        return $this->client->loginUrl($this->requestedLocale());
    }

    public function authenticate(Request $request): SsoIdentity
    {
        return $this->identityFromToken((string) $request->query->get('token'));
    }

    /** @throws SsoAuthenticationFailedException */
    public function identityFromToken(string $token): SsoIdentity
    {
        if ('' === $token || !$this->client->checkToken($token)) {
            throw SsoAuthenticationFailedException::invalidToken(self::KEY);
        }

        try {
            $payload = $this->jwtDecoder->decode($token);
        } catch (\Throwable $e) {
            throw SsoAuthenticationFailedException::fromProvider(self::KEY, $e);
        }

        $email = $payload['email'] ?? null;

        if (!is_string($email) || '' === $email) {
            throw SsoAuthenticationFailedException::missingEmail(self::KEY);
        }

        return new SsoIdentity(mb_strtolower($email));
    }

    private function requestedLocale(): Locale
    {
        $locale = Locale::tryFrom((string) $this->requestStack->getCurrentRequest()?->query->get('locale'));

        return $locale ?? LocaleGetter::locale();
    }
}
