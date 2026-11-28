<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\Close;

use App\CarbonFootprint\Application\Contest\ContestAuthorizer;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class CloseContestService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ContestRepository $contestRepository,
        private ContestAuthorizer $authorizer,
    ) {}

    public function __invoke(CloseContestRequest $request, User $user): CloseContestResponse
    {
        $contest = $this->contestRepository->find($request->contestId);

        if (!$contest instanceof Contest) {
            throw new \InvalidArgumentException('Contest not found');
        }

        $this->authorizer->denyUnlessCanDoThat($contest, Action::Edit, $user);

        $contest->close(new \DateTimeImmutable('NOW'));
        $this->contestRepository->add($contest);

        return new CloseContestResponse($contest);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CloseContestRequest;
    }
}
