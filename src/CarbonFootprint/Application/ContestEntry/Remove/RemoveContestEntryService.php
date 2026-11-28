<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ContestEntry\Remove;

use App\CarbonFootprint\Application\Contest\ContestAuthorizer;
use App\CarbonFootprint\Application\ContestEntry\ContestPrizeReassigner;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestLock;
use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntry;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\Transaction\TransactionalSession;

class RemoveContestEntryService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ContestRepository $contestRepository,
        private ContestEntryRepository $contestEntryRepository,
        private ContestAuthorizer $authorizer,
        private ContestLock $contestLock,
        private ContestPrizeReassigner $prizeReassigner,
        private TransactionalSession $transactionalSession,
    ) {}

    public function __invoke(RemoveContestEntryRequest $request, User $user): RemoveContestEntryResponse
    {
        $contest = $this->contestRepository->find($request->contestId);

        if (!$contest instanceof Contest) {
            throw new \InvalidArgumentException('Contest not found');
        }

        $this->authorizer->denyUnlessCanDoThat($contest, Action::Remove, $user);

        $entry = $this->contestEntryRepository->find($request->contestEntryId);

        if (!$entry instanceof ContestEntry || !($entry->contestId()?->equals($contest->id()) ?? false)) {
            throw new \InvalidArgumentException('Contest entry not found');
        }

        $this->transactionalSession->run(function () use ($contest, $entry): void {
            $this->contestLock->acquire($contest->id());
            $this->contestEntryRepository->refresh($entry);

            $now = new \DateTimeImmutable('NOW');
            $wasWinner = $entry->isWinner();

            $entry->remove();
            $this->contestEntryRepository->remove($entry);

            if ($wasWinner) {
                $this->prizeReassigner->reassign($contest, $now);
            }
        });

        return new RemoveContestEntryResponse($entry);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveContestEntryRequest;
    }
}
