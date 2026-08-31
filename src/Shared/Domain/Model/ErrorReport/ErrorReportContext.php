<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\ErrorReport;

class ErrorReportContext
{
    /**
     * @param string[] $roles
     */
    public function __construct(
        public ?string $userId = null,
        public ?string $username = null,
        public array $roles = [],
        public bool $impersonated = false,
        public ?string $impersonatorId = null,
        public ?string $impersonatorUsername = null,
        public ?string $currentAccountId = null,
        public ?string $currentAccountName = null,
        public ?string $ownerAccountId = null,
    ) {}
}
