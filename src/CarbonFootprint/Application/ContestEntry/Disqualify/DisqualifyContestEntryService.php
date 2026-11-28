<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ContestEntry\Disqualify;

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

class DisqualifyContestEntryService implements CommandHandler
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

    public function __invoke(DisqualifyContestEntryRequest $request, User $user): DisqualifyContestEntryResponse
    {
        $contest = $this->contestRepository->find($request->contestId);

        if (!$contest instanceof Contest) {
            throw new \InvalidArgumentException('Contest not found');
        }

        $this->authorizer->denyUnlessCanDoThat($contest, Action::Edit, $user);

        $entry = $this->contestEntryRepository->find($request->contestEntryId);

        if (!$entry instanceof ContestEntry || !($entry->contestId()?->equals($contest->id()) ?? false)) {
            throw new \InvalidArgumentException('Contest entry not found');
        }

        $this->transactionalSession->run(function () use ($contest, $entry, $request, $user): void {
            $this->contestLock->acquire($contest->id());
            $this->contestEntryRepository->refresh($entry);

            $now = new \DateTimeImmutable('NOW');
            $wasWinner = $entry->isWinner();

            $entry->disqualify($user->id(), $request->reason, $now);
            $this->contestEntryRepository->add($entry);

            if ($wasWinner) {
                $this->prizeReassigner->reassign($contest, $now);
            }
        });

        return new DisqualifyContestEntryResponse($entry);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof DisqualifyContestEntryRequest;
    }
}
