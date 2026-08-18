<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Cancel;

use App\Payments\Application\Payout\PayoutAuthorizer;
use App\Payments\Domain\Model\Payout\PayoutRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class CancelPayoutService
{
    public function __construct(
        private PayoutRepository $payoutRepository,
        private PayoutAuthorizer $authorizer,
    ) {}

    public function __invoke(CancelPayoutRequest $request, User $user): CancelPayoutResponse
    {
        $payout = $this->payoutRepository->find($request->payoutId);

        if (null === $payout) {
            throw new \InvalidArgumentException('Payout not found');
        }

        $this->authorizer->denyUnlessCanDoThat($payout, SecurityAction::Remove, $user);

        $payout->cancel();

        $this->payoutRepository->add($payout);

        return new CancelPayoutResponse($payout->status());
    }
}
