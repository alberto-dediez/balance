<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Sso\Bioscore;

interface BioscoreJwtDecoder
{
    /**
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    public function decode(string $token): array;
}
