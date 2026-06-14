<?php

declare(strict_types=1);

namespace App\Notifications\Domain\Model\Notification;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;

final class NotificationAudience
{
    public function __construct(
        public readonly AudienceType $type,
        public readonly ?UserId $userId = null,
        public readonly ?AccountId $accountId = null,
        public readonly ?Role $role = null,
    ) {}

    public static function user(UserId $userId): self
    {
        return new self(AudienceType::User, userId: $userId);
    }

    public static function account(AccountId $accountId): self
    {
        return new self(AudienceType::Account, accountId: $accountId);
    }

    public static function accountRole(AccountId $accountId, Role $role): self
    {
        return new self(AudienceType::AccountRole, accountId: $accountId, role: $role);
    }

    public static function allPlatform(): self
    {
        return new self(AudienceType::AllPlatform);
    }
}
