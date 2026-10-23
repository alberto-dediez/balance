<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculation\GetAccountResults;

use App\CarbonFootprint\Application\TripCalculation\GetResults\GetTripCalculationResultsResponse;
use App\CarbonFootprint\Application\TripCalculation\GetResults\TripCalculationResultsBuilder;
use App\CarbonFootprint\Application\TripCalculationLink\TripCalculationLinkAuthorizer;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;

final class GetAccountTripCalculatorResultsService
{
    public function __construct(
        private CurrentAccountIdProvider $currentAccountIdProvider,
        private AccountRepository $accountRepository,
        private TripCalculationLinkRepository $tripCalculationLinkRepository,
        private TripCalculationLinkAuthorizer $tripCalculationLinkAuthorizer,
        private TripCalculationResultsBuilder $tripCalculationResultsBuilder,
    ) {}

    public function __invoke(GetAccountTripCalculatorResultsRequest $request, User $user): GetTripCalculationResultsResponse
    {
        $accountId = $this->currentAccountIdProvider->currentAccountId();

        if (null === $accountId) {
            throw new \InvalidArgumentException('No current account');
        }

        $tripCalculationLink = $this->tripCalculationLinkRepository->findForAccount($accountId);

        $this->tripCalculationLinkAuthorizer->denyUnlessCanDoThat(
            $tripCalculationLink ?? new TripCalculationLink(id: TripCalculationLinkId::generate(), accountId: $accountId),
            Action::View,
            $user,
        );

        $account = $this->accountRepository->find($accountId);

        if (null === $account) {
            throw new \InvalidArgumentException('Account not found');
        }

        if (null === $tripCalculationLink) {
            return $this->tripCalculationResultsBuilder->empty(null, $account->name(), null);
        }

        return $this->tripCalculationResultsBuilder->build(
            $tripCalculationLink,
            null,
            $account->name(),
            null,
            $request->from,
            $request->to,
        );
    }
}
