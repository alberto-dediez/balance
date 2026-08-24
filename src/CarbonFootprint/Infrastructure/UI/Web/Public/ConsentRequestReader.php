<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public;

use Symfony\Component\HttpFoundation\Request;

final class ConsentRequestReader
{
    public static function accepted(Request $request): bool
    {
        return '' !== $request->request->getString('consent_accepted');
    }

    public static function ip(Request $request): string
    {
        return $request->getClientIp() ?? '';
    }

    public static function userAgent(Request $request): string
    {
        return $request->headers->get('User-Agent') ?? '';
    }

    public static function acceptLanguage(Request $request): string
    {
        return $request->headers->get('Accept-Language') ?? '';
    }
}
