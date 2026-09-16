<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Sso;

final readonly class SsoIdentity
{
    public function __construct(
        public string $email,
        public ?string $name = null,
    ) {}
}
