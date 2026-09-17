<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final readonly class CsrfProtected
{
    public function __construct(
        public string $tokenId,
        public string $parameter = '_token',
    ) {}
}
