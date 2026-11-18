<?php

declare(strict_types=1);

namespace App\Tests\Unit\Development\Infrastructure\Mercure;

use Symfony\Component\Mercure\Jwt\Grant;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;

class SpyTokenFactory implements TokenFactoryInterface
{
    /** @var list<array{grants: array<Grant>, claims: array<mixed>}> */
    public array $tokens = [];

    public function create(array $grants = [], array $additionalClaims = []): string
    {
        $this->tokens[] = ['grants' => $grants, 'claims' => $additionalClaims];

        return 'subscriber-token';
    }
}
