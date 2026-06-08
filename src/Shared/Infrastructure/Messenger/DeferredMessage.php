<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CommandBus\Command;
use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
class DeferredMessage
{
    public function __construct(
        private readonly Command $command,
        private readonly UserId $userId,
    ) {}

    public function command(): Command
    {
        return $this->command;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }
}
