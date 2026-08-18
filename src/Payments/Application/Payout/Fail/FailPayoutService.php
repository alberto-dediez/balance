<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Fail;

use App\Payments\Application\Payout\PayoutAuthorizer;
use App\Payments\Domain\Model\Payout\PayoutRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class FailPayoutService
{
    public function __construct(
        private PayoutRepository $payoutRepository,
        private PayoutAuthorizer $authorizer,
    ) {}

    public function __invoke(FailPayoutRequest $request, User $user): FailPayoutResponse
    {
        $payout = $this->payoutRepository->find($request->payoutId);

        if (null === $payout) {
            throw new \InvalidArgumentException('Payout not found');
        }

        $this->authorizer->denyUnlessCanDoThat($payout, SecurityAction::Edit, $user);

        $payout->fail($request->reason);

        $this->payoutRepository->add($payout);

        return new FailPayoutResponse($payout->status());
    }
}
