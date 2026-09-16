<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Sso;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class SsoProviderRegistry
{
    /** @param iterable<SsoProvider> $providers */
    public function __construct(
        #[AutowireIterator('security.sso_provider')]
        private iterable $providers,
    ) {}

    /** @throws UnknownSsoProviderException */
    public function get(string $key): SsoProvider
    {
        foreach ($this->providers as $provider) {
            if ($key === $provider->key()) {
                return $provider;
            }
        }

        throw new UnknownSsoProviderException($key);
    }

    /** @return list<SsoProvider> */
    public function enabled(): array
    {
        $enabled = [];

        foreach ($this->providers as $provider) {
            if ($provider->isEnabled()) {
                $enabled[] = $provider;
            }
        }

        return $enabled;
    }

    /** @return list<SsoProvider> */
    public function all(): array
    {
        return iterator_to_array($this->providers, false);
    }
}
