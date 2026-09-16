<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Sso\Bioscore;

use App\Shared\Domain\Model\Locale\Locale;

interface BioscoreClient
{
    public function loginUrl(Locale $locale): string;

    public function checkToken(string $token): bool;
}
