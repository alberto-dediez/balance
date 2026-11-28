<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\CloseExpired;

use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class CloseExpiredContestsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ContestRepository $contestRepository,
    ) {}

    public function __invoke(CloseExpiredContestsRequest $request, User $systemActor): CloseExpiredContestsResponse
    {
        $closed = 0;

        foreach ($this->contestRepository->findPublishedEndedBefore($request->now) as $contest) {
            $contest->close($request->now);
            $this->contestRepository->add($contest);
            ++$closed;
        }

        return new CloseExpiredContestsResponse($closed);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CloseExpiredContestsRequest;
    }
}
