<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\SSO;

use App\Shared\Domain\Model\Locale\Locale;

interface SSOClient
{
    public function getLoginUrl(Locale $locale = Locale::Spanish): string;

    public function checkToken(string $token): bool;
}
