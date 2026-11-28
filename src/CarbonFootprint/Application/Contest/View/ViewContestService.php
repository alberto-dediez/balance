<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\View;

use App\CarbonFootprint\Application\Contest\ContestAuthorizer;
use App\CarbonFootprint\Application\Contest\ContestHostLocator;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

class ViewContestService
{
    public function __construct(
        private ContestRepository $contestRepository,
        private ContestAuthorizer $authorizer,
        private ContestHostLocator $hostLocator,
    ) {}

    public function __invoke(ViewContestRequest $request, User $user): ViewContestResponse
    {
        $contest = $this->contestRepository->find($request->contestId);

        if (!$contest instanceof Contest) {
            throw new \InvalidArgumentException('Contest not found');
        }

        $this->authorizer->denyUnlessCanDoThat($contest, Action::View, $user);

        $hostNames = [];

        foreach ($contest->hostReferences() as $host) {
            $hostNames[$host->key()] = $this->hostLocator->name($host);
        }

        return new ViewContestResponse($contest, $hostNames);
    }
}
