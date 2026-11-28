<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\Draw;

use App\CarbonFootprint\Application\Contest\ContestAuthorizer;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestAlreadyResolvedException;
use App\CarbonFootprint\Domain\Model\Contest\ContestLock;
use App\CarbonFootprint\Domain\Model\Contest\ContestNotEditableException;
use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\CarbonFootprint\Domain\Model\Contest\ContestWinnerPicker;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\Transaction\TransactionalSession;

class DrawContestWinnersService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ContestRepository $contestRepository,
        private ContestAuthorizer $authorizer,
        private ContestEntryRepository $contestEntryRepository,
        private ContestLock $contestLock,
        private ContestWinnerPicker $winnerPicker,
        private TransactionalSession $transactionalSession,
    ) {}

    public function __invoke(DrawContestWinnersRequest $request, User $user): DrawContestWinnersResponse
    {
        $contest = $this->contestRepository->find($request->contestId);

        if (!$contest instanceof Contest) {
            throw new \InvalidArgumentException('Contest not found');
        }

        $this->authorizer->denyUnlessCanDoThat($contest, Action::Edit, $user);

        $this->transactionalSession->run(function () use ($contest, $user): void {
            $this->contestLock->acquire($contest->id());

            if (!$contest->isClosed()) {
                throw $contest->isResolved() ? new ContestAlreadyResolvedException() : new ContestNotEditableException('Solo se puede sortear un concurso cerrado.');
            }

            if (!$contest->requiresPlatformDraw()) {
                throw new ContestNotEditableException('Este concurso no se sortea en la plataforma.');
            }

            $now = new \DateTimeImmutable('NOW');
            $candidates = $this->contestEntryRepository->findEligibleWithoutPrizeIds($contest->id());
            $picked = $this->winnerPicker->pick($candidates, min((int) $contest->winnersCount(), count($candidates)));

            foreach ($picked as $entryId) {
                $entry = $this->contestEntryRepository->find($entryId);

                if (null === $entry) {
                    continue;
                }

                $entry->award($now);
                $this->contestEntryRepository->add($entry);
            }

            $contest->resolveByDraw($user->id(), count($candidates), $now);
            $this->contestRepository->add($contest);
        });

        return new DrawContestWinnersResponse($contest);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof DrawContestWinnersRequest;
    }
}
