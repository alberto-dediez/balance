<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Contest\List;

use App\CarbonFootprint\Application\Contest\ContestAuthorizer;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;

class ListContestsService
{
    public function __construct(
        private ContestRepository $contestRepository,
        private ContestAuthorizer $authorizer,
        private CurrentAccountIdProvider $currentAccountIdProvider,
    ) {}

    public function __invoke(ListContestsRequest $request, User $user): ListContestsResponse
    {
        $accountId = $this->currentAccountIdProvider->currentAccountId();

        $this->authorizer->denyUnlessCanDoThat(
            new Contest(id: ContestId::generate(), accountId: $accountId),
            Action::List,
            $user,
        );

        $request->accountId = $accountId;

        return new ListContestsResponse(
            $this->contestRepository->findByFilter($request),
            $this->contestRepository->countByFilter($request),
        );
    }
}
