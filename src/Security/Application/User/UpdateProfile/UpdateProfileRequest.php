<?php

declare(strict_types=1);

namespace App\Security\Application\User\UpdateProfile;

use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Domain\Model\Locale\Locale;

class UpdateProfileRequest implements Command
{
    public function __construct(
        public UserId $userId,
        public Locale $preferredLocale,
    ) {}
}
