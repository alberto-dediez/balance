<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\View;

use App\Payments\Application\Payout\PayoutAuthorizer;
use App\Payments\Domain\Model\Payee\PayeeId;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Payments\Domain\Model\Payee\PayeeType;
use App\Payments\Domain\Model\Payout\Payout;
use App\Payments\Domain\Model\Payout\PayoutRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;

final class ViewPayoutService
{
    public function __construct(
        private PayoutRepository $repository,
        private PayeeRepository $payeeRepository,
        private PayoutAuthorizer $authorizer,
    ) {}

    public function __invoke(ViewPayoutRequest $request, User $user): ViewPayoutResponse
    {
        $payout = $this->repository->find($request->payoutId);

        if (null === $payout) {
            throw new \InvalidArgumentException('Payout not found');
        }

        $this->authorizer->denyUnlessCanDoThat($payout, SecurityAction::View, $user);

        $this->denyForeignPayout($payout, $user);

        $payeeId = $payout->payeeId();

        return new ViewPayoutResponse(
            payout: $payout,
            payee: $payeeId instanceof PayeeId ? $this->payeeRepository->find($payeeId) : null,
        );
    }

    private function denyForeignPayout(Payout $payout, User $user): void
    {
        if ($user->hasRole(Role::SuperAdmin) || $user->hasRole(Role::Admin)) {
            return;
        }

        $accountId = $user->accountId();

        $payee = $accountId instanceof AccountId
            ? $this->payeeRepository->findOneBySubject(PayeeType::Account, (string) $accountId)
            : null;

        if (null === $payee || (string) $payee->id() !== (string) $payout->payeeId()) {
            throw new UnauthorizedActionException($payout, SecurityAction::View);
        }
    }
}
