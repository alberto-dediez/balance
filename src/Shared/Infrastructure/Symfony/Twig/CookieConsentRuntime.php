<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Twig;

use App\Shared\Infrastructure\UI\Web\CookieConsent\CookieConsentDecision;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\RuntimeExtensionInterface;

class CookieConsentRuntime implements RuntimeExtensionInterface
{
    public function __construct(private readonly RequestStack $requestStack) {}

    public function name(): string
    {
        return CookieConsentDecision::COOKIE_NAME;
    }

    public function maxAge(): int
    {
        return CookieConsentDecision::COOKIE_MAX_AGE;
    }

    public function value(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return null;
        }

        $value = $request->cookies->get(CookieConsentDecision::COOKIE_NAME);

        return CookieConsentDecision::tryFrom(is_string($value) ? $value : '')?->value;
    }
}
