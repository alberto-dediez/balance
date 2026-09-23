<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Timezone;

use App\Shared\Application\CurrentTimezone\CurrentTimezoneProvider;
use Symfony\Component\HttpFoundation\RequestStack;

class CookieTimezoneProvider implements CurrentTimezoneProvider
{
    public const string COOKIE = 'timezone';

    public function __construct(private readonly RequestStack $requestStack) {}

    public function currentTimezone(): \DateTimeZone
    {
        $timezone = $this->requestStack->getMainRequest()?->cookies->getString(self::COOKIE) ?? '';

        if (in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            return new \DateTimeZone($timezone);
        }

        return new \DateTimeZone(date_default_timezone_get());
    }
}
