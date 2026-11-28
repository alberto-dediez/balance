<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ContestEntry\CheckEligibility;

use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestNotAcceptingEntriesException;
use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntry;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryCountryNotAllowedException;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryRepository;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Domain\Model\Account\AccountFeature;

class CheckContestEntryEligibilityService
{
    public function __construct(
        private ContestRepository $contestRepository,
        private ContestEntryRepository $contestEntryRepository,
        private AccountFeaturesPolicy $accountFeaturesPolicy,
    ) {}

    public function __invoke(CheckContestEntryEligibilityRequest $request): CheckContestEntryEligibilityResponse
    {
        $contest = $this->contestRepository->find($request->contestId);

        if (
            !$contest instanceof Contest
            || !$contest->acceptsEntries($request->now)
            || !$contest->hasHost($request->host)
            || !$this->accountFeaturesPolicy->isEnabled(AccountFeature::Contests, $contest->accountId())
        ) {
            throw new ContestNotAcceptingEntriesException();
        }

        if (!$request->consentAccepted) {
            throw new \InvalidArgumentException('Debes aceptar las bases y la cesión de datos para participar.');
        }

        if ($contest->adultsOnly() && !$request->adultConfirmed) {
            throw new \InvalidArgumentException('Debes confirmar que eres mayor de edad para participar.');
        }

        if (!$contest->allowsCountry($request->country)) {
            throw new ContestEntryCountryNotAllowedException($request->country);
        }

        $pendingEntry = null;
        $byOrigin = $this->contestEntryRepository->findOneByOrigin($contest->id(), $request->origin, $request->originId);

        if ($byOrigin instanceof ContestEntry) {
            if (!$byOrigin->isPendingPayment()) {
                throw new ContestEntryAlreadyExistsException();
            }

            $pendingEntry = $byOrigin;
        }

        $email = mb_strtolower((string) preg_replace('/\s+/', '', $request->email));
        $byEmail = '' !== $email ? $this->contestEntryRepository->findOneByEmail($contest->id(), $email) : null;

        if ($byEmail instanceof ContestEntry && !($pendingEntry instanceof ContestEntry && $byEmail->id()->equals($pendingEntry->id()))) {
            throw new ContestEntryAlreadyExistsException();
        }

        return new CheckContestEntryEligibilityResponse($contest, $pendingEntry);
    }
}
