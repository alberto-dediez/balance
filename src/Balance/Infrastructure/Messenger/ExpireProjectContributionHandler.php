<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Messenger;

use App\Balance\Application\ProjectContribution\Expire\ExpireProjectContributionCommand;
use App\Balance\Application\ProjectContribution\Expire\ExpireProjectContributionRequest;
use App\Balance\Application\ProjectContribution\Expire\ExpireProjectContributionService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ExpireProjectContributionHandler
{
    public function __construct(private ExpireProjectContributionService $expireProjectContribution) {}

    public function __invoke(ExpireProjectContributionCommand $command): void
    {
        $this->expireProjectContribution->__invoke(
            new ExpireProjectContributionRequest($command->projectContributionId()),
        );
    }
}
