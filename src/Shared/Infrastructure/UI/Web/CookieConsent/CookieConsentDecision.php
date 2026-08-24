<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\CookieConsent;

enum CookieConsentDecision: string
{
    public const string COOKIE_NAME = 'cookie_consent';
    public const int COOKIE_MAX_AGE = 15552000;

    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
