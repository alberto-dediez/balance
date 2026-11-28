<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\GetActiveForHost;

use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Domain\Model\Account\AccountFeature;

class GetActiveContestForHostService
{
    public function __construct(
        private ContestRepository $contestRepository,
        private AccountFeaturesPolicy $accountFeaturesPolicy,
    ) {}

    public function __invoke(GetActiveContestForHostRequest $request): GetActiveContestForHostResponse
    {
        $contest = $this->contestRepository->findPublishedByHost($request->host);

        if (!$contest instanceof Contest || !$contest->acceptsEntries($request->now)) {
            return new GetActiveContestForHostResponse();
        }

        if (!$this->accountFeaturesPolicy->isEnabled(AccountFeature::Contests, $contest->accountId())) {
            return new GetActiveContestForHostResponse();
        }

        return new GetActiveContestForHostResponse($contest);
    }
}
