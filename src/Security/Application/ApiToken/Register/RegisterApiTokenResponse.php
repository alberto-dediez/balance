<?php

declare(strict_types=1);

namespace App\Security\Application\ApiToken\Register;

use App\Security\Domain\Model\ApiToken\ApiTokenId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterApiTokenResponse implements CommandResponse
{
    public function __construct(
        public ApiTokenId $apiTokenId,
        public string $name,
        #[\SensitiveParameter]
        public string $plainValue,
        public ?\DateTimeImmutable $expiresAt,
    ) {}
}
