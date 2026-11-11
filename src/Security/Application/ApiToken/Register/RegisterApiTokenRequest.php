<?php

declare(strict_types=1);

namespace App\Security\Application\ApiToken\Register;

use App\Shared\Application\CommandBus\Command;

class RegisterApiTokenRequest implements Command
{
    public function __construct(
        public string $name = '',
        public ?\DateTimeImmutable $expiresAt = null,
    ) {}
}
