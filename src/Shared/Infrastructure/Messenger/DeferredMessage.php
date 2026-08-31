<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\Command;

class DeferredMessage
{
    public function __construct(
        private readonly Command $command,
        private readonly UserId $userId,
        private readonly ?AccountId $impersonatedAccountId = null,
    ) {}

    public function command(): Command
    {
        return $this->command;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function impersonatedAccountId(): ?AccountId
    {
        return $this->impersonatedAccountId ?? null;
    }
}
