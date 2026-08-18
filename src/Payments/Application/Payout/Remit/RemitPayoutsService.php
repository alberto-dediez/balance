<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Remit;

use App\Payments\Application\Payout\PayoutAuthorizer;
use App\Payments\Domain\Model\Payout\Payout;
use App\Payments\Domain\Model\Payout\PayoutFilter;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Payments\Domain\Model\Payout\PayoutReference;
use App\Payments\Domain\Model\Payout\PayoutRepository;
use App\Payments\Domain\Model\Payout\PayoutStatus;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class RemitPayoutsService
{
    public function __construct(
        private PayoutRepository $payoutRepository,
        private PayoutAuthorizer $authorizer,
    ) {}

    public function __invoke(RemitPayoutsRequest $request, User $user): RemitPayoutsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new Payout(id: PayoutId::generate()), SecurityAction::Admin, $user);

        $reference = new PayoutReference(gateway: $request->format, externalId: $request->reference);
        $remitted = 0;

        foreach ($this->payouts($request) as $payout) {
            $payout->send($reference);

            $this->payoutRepository->add($payout);

            ++$remitted;
        }

        return new RemitPayoutsResponse($remitted);
    }

    /** @return list<Payout> */
    private function payouts(RemitPayoutsRequest $request): array
    {
        if ([] === $request->payoutIds) {
            return array_values($this->payoutRepository->findByFilter(new PayoutFilter(status: PayoutStatus::Draft, limit: 0)));
        }

        $payouts = [];

        foreach ($request->payoutIds as $payoutId) {
            $payout = $this->payoutRepository->find($payoutId);

            if (null !== $payout && $payout->isSendable()) {
                $payouts[] = $payout;
            }
        }

        return $payouts;
    }
}
