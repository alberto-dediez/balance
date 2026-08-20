<?php

declare(strict_types=1);

namespace App\Security\Application\Branding\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class RegisterBrandingRequest implements Command
{
    public function __construct(
        public AccountId $accountId,
        public string $themeSlug = 'default',
    ) {}
}
