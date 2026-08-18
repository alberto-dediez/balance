<?php

declare(strict_types=1);

namespace App\Payments\Application\Payout\Send;

use App\Payments\Application\Payout\PayoutAuthorizer;
use App\Payments\Domain\Model\Payee\PayeeId;
use App\Payments\Domain\Model\Payee\PayeeRepository;
use App\Payments\Domain\Model\Payout\PayeeNotReadyException;
use App\Payments\Domain\Model\Payout\Payout;
use App\Payments\Domain\Model\Payout\PayoutGateway;
use App\Payments\Domain\Model\Payout\PayoutGatewayException;
use App\Payments\Domain\Model\Payout\PayoutInstruction;
use App\Payments\Domain\Model\Payout\PayoutRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

final class SendPayoutService
{
    public function __construct(
        private PayoutRepository $payoutRepository,
        private PayeeRepository $payeeRepository,
        private PayoutGateway $payoutGateway,
        private PayoutAuthorizer $authorizer,
    ) {}

    public function __invoke(SendPayoutRequest $request, User $user): SendPayoutResponse
    {
        $payout = $this->payoutRepository->find($request->payoutId);

        if (null === $payout) {
            throw new \InvalidArgumentException('Payout not found');
        }

        $this->authorizer->denyUnlessCanDoThat($payout, SecurityAction::Edit, $user);

        try {
            $payout->send($this->payoutGateway->send($this->instructionFor($payout)));
        } catch (PayoutGatewayException $e) {
            $payout->fail($e->getMessage());
        }

        $this->payoutRepository->add($payout);

        return new SendPayoutResponse(status: $payout->status(), externalId: $payout->externalId());
    }

    private function instructionFor(Payout $payout): PayoutInstruction
    {
        $payeeId = $payout->payeeId();

        $payee = $payeeId instanceof PayeeId ? $this->payeeRepository->find($payeeId) : null;

        if (null === $payee) {
            throw new \InvalidArgumentException('Payee not found');
        }

        if (!$payee->isReady()) {
            throw new PayeeNotReadyException($payee->id());
        }

        return new PayoutInstruction(
            payoutId: $payout->id(),
            number: (string) $payout->number(),
            amount: $payout->payableAmount(),
            currency: $payout->currency(),
            concept: sprintf('Liquidacion %s', (string) $payout->number()),
            holderName: (string) ($payee->holderName() ?? $payee->legalName()),
            iban: $payee->iban(),
            bic: $payee->bic(),
            externalId: $payee->externalId(),
            metadata: ['payout_id' => (string) $payout->id()],
        );
    }
}
