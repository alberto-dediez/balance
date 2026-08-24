<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CookieConsentExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('cookie_consent_value', [CookieConsentRuntime::class, 'value']),
            new TwigFunction('cookie_consent_name', [CookieConsentRuntime::class, 'name']),
            new TwigFunction('cookie_consent_max_age', [CookieConsentRuntime::class, 'maxAge']),
        ];
    }
}
