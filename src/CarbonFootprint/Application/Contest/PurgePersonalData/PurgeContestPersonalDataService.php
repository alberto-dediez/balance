<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\PurgePersonalData;

use App\CarbonFootprint\Application\Contest\ContestAuthorizer;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestNotEditableException;
use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryFilter;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\Transaction\TransactionalSession;

class PurgeContestPersonalDataService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ContestRepository $contestRepository,
        private ContestAuthorizer $authorizer,
        private ContestEntryRepository $contestEntryRepository,
        private TransactionalSession $transactionalSession,
    ) {}

    public function __invoke(PurgeContestPersonalDataRequest $request, User $user): PurgeContestPersonalDataResponse
    {
        $contest = $this->contestRepository->find($request->contestId);

        if (!$contest instanceof Contest) {
            throw new \InvalidArgumentException('Contest not found');
        }

        $this->authorizer->denyUnlessCanDoThat($contest, Action::Remove, $user);

        if (!$contest->isClosed() && !$contest->isResolved()) {
            throw new ContestNotEditableException('Solo se pueden purgar los datos de un concurso cerrado.');
        }

        $this->transactionalSession->run(function () use ($contest, $user): void {
            $now = new \DateTimeImmutable('NOW');
            $entries = $this->contestEntryRepository->findByFilter(new ContestEntryFilter(contestId: $contest->id(), limit: 0));

            foreach ($entries as $entry) {
                if ($entry->isPendingPayment()) {
                    $entry->remove();
                    $this->contestEntryRepository->remove($entry);

                    continue;
                }

                $entry->purgePersonalData($now);
                $this->contestEntryRepository->add($entry);
            }

            $contest->purgePersonalData($user->id(), $now);
            $this->contestRepository->add($contest);
        });

        return new PurgeContestPersonalDataResponse($contest);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof PurgeContestPersonalDataRequest;
    }
}
