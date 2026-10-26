<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Reallocate;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\TimedCommand;

final class ReallocateAllUnderallocatedContributionsRequest implements TimedCommand
{
    public function __construct(
        public readonly ?AccountId $accountId = null,
        public readonly ?ProjectId $projectId = null,
    ) {}

    public function withNow(\DateTimeImmutable $now): self
    {
        return $this;
    }
}
