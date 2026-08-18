<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Create;

use App\Payments\Application\Payout\PayoutAuthorizer;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Payments\Domain\Model\Payout\PayeeNotReadyException;
use App\Payments\Domain\Model\Payout\Payout;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Payments\Domain\Model\Payout\PayoutNumberGenerator;
use App\Payments\Domain\Model\Payout\PayoutRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class CreatePayoutService
{
    public function __construct(
        private PayoutRepository $payoutRepository,
        private PayeeRepository $payeeRepository,
        private PayoutNumberGenerator $numberGenerator,
        private PayoutCreationLock $lock,
        private PayoutAuthorizer $authorizer,
    ) {}

    public function __invoke(CreatePayoutRequest $request, User $user): CreatePayoutResponse
    {
        $payout = new Payout(id: PayoutId::generate());

        $this->authorizer->denyUnlessCanDoThat($payout, SecurityAction::Register, $user);

        $payee = $this->payeeRepository->find($request->payeeId);

        if (null === $payee) {
            throw new \InvalidArgumentException('Payee not found');
        }

        if (!$payee->isReady()) {
            throw new PayeeNotReadyException($payee->id());
        }

        $now = new \DateTimeImmutable('NOW');

        $this->lock->withLock(function () use ($payout, $payee, $request, $user, $now): void {
            $payout->create(
                number: $this->numberGenerator->next($now),
                payeeId: $payee->id(),
                baseAmount: round($request->baseAmount, 2),
                currency: $request->currency,
                linesCount: $request->linesCount,
                createdBy: $user->id(),
                periodFrom: $request->periodFrom,
                periodTo: $request->periodTo,
                notes: $request->notes,
            );

            $this->payoutRepository->add($payout);
        });

        return new CreatePayoutResponse(payoutId: $payout->id(), number: (string) $payout->number());
    }
}
