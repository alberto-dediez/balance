<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\List;

use App\Payments\Application\Payout\PayoutAuthorizer;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Payments\Domain\Model\Payout\Payout;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Payments\Domain\Model\Payout\PayoutRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

class ListPayoutsService
{
    public function __construct(
        private PayoutRepository $repository,
        private PayeeRepository $payeeRepository,
        private PayoutAuthorizer $authorizer,
    ) {}

    public function __invoke(ListPayoutsRequest $request, User $user): ListPayoutsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Payout(id: PayoutId::generate()), SecurityAction::List, $user);

        $filter = $this->restrictToOwnPayee($request, $user);

        if (null === $filter) {
            return new ListPayoutsResponse();
        }

        return new ListPayoutsResponse(
            $this->repository->findByFilter($filter),
            $this->repository->totalResultsByFilter($filter),
        );
    }

    private function restrictToOwnPayee(ListPayoutsRequest $request, User $user): ?ListPayoutsRequest
    {
        if ($user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin)) {
            return $request;
        }

        $accountId = $user->accountId();

        if (!$accountId instanceof AccountId) {
            return null;
        }

        $payee = $this->payeeRepository->findOneBySubject(PayeeType::Account, (string) $accountId);

        if (null === $payee) {
            return null;
        }

        $filter = clone $request;
        $filter->payeeId = $payee->id();
        $filter->payeeIds = null;

        return $filter;
    }
}
