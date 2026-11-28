<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ContestEntry\Register;

use App\CarbonFootprint\Application\ContestEntry\CheckEligibility\CheckContestEntryEligibilityRequest;
use App\CarbonFootprint\Application\ContestEntry\CheckEligibility\CheckContestEntryEligibilityService;
use App\CarbonFootprint\Application\ContestEntry\ContestEntryConfirmer;
use App\CarbonFootprint\Domain\Model\Contest\ContestLock;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntry;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryId;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryRepository;
use App\Shared\Application\Transaction\TransactionalSession;

class RegisterContestEntryService
{
    public function __construct(
        private CheckContestEntryEligibilityService $checkEligibility,
        private ContestEntryRepository $contestEntryRepository,
        private ContestEntryConfirmer $confirmer,
        private ContestLock $contestLock,
        private TransactionalSession $transactionalSession,
    ) {}

    public function __invoke(RegisterContestEntryRequest $request): RegisterContestEntryResponse
    {
        return $this->transactionalSession->run(function () use ($request): RegisterContestEntryResponse {
            $eligibility = ($this->checkEligibility)(new CheckContestEntryEligibilityRequest(
                contestId: $request->contestId,
                host: $request->host,
                origin: $request->origin,
                originId: $request->originId,
                email: $request->email,
                country: $request->country,
                consentAccepted: $request->consentAccepted,
                adultConfirmed: $request->adultConfirmed,
                now: $request->now,
            ));

            $contest = $eligibility->contest;
            $accountId = $contest->accountId();
            assert(null !== $accountId);

            $projectContributionId = $contest->participationMode()->isCompensation() ? $request->projectContributionId : null;

            if ($contest->participationMode()->isCompensation() && null === $projectContributionId) {
                throw new \InvalidArgumentException('La inscripción en un concurso por compensación requiere una aportación.');
            }

            $pending = $eligibility->pendingEntry;

            if ($pending instanceof ContestEntry && null !== $projectContributionId) {
                $pending->renewPendingPayment(
                    projectContributionId: $projectContributionId,
                    name: $request->name,
                    email: $request->email,
                    phone: $request->phone,
                    country: $request->country,
                    locale: $request->locale,
                    consentProof: $request->consentProof,
                    at: $request->now,
                );
                $this->contestEntryRepository->add($pending);

                return new RegisterContestEntryResponse($pending);
            }

            $entry = new ContestEntry(ContestEntryId::generate());
            $entry->register(
                contestId: $contest->id(),
                accountId: $accountId,
                host: $request->host,
                origin: $request->origin,
                originId: $request->originId,
                projectContributionId: $projectContributionId,
                name: $request->name,
                email: $request->email,
                phone: $request->phone,
                country: $request->country,
                locale: $request->locale,
                consentProof: $request->consentProof,
                at: $request->now,
            );

            if (null !== $projectContributionId) {
                $this->contestEntryRepository->add($entry);

                return new RegisterContestEntryResponse($entry);
            }

            $this->contestLock->acquire($contest->id());
            $this->confirmer->confirm($contest, $entry, $request->now);

            return new RegisterContestEntryResponse($entry);
        });
    }
}
