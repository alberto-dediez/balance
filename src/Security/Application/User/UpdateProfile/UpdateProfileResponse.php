<?php

declare(strict_types=1);

namespace App\Security\Application\User\UpdateProfile;

use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateProfileResponse implements CommandResponse
{
    public function __construct(public readonly UserId $userId) {}
}
