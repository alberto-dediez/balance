<?php

declare(strict_types=1);

namespace App\Payments\Application\Payment\Confirm;

use App\Payments\Domain\Model\Payment\PaymentRepository;

/**
 * Idempotent: the webhook and the browser return may arrive in any order and more than once,
 * so confirming an already resolved payment is a no-op.
 */
final class ConfirmPaymentService
{
    public function __construct(private PaymentRepository $paymentRepository) {}

    public function __invoke(ConfirmPaymentRequest $request): ConfirmPaymentResponse
    {
        $payment = $this->paymentRepository->find($request->paymentId);

        if (null === $payment) {
            throw new \InvalidArgumentException('Payment not found');
        }

        if (!$payment->isPending()) {
            return new ConfirmPaymentResponse(status: $payment->status(), changed: false);
        }

        $payment->succeed($request->externalPaymentId);
        $this->paymentRepository->add($payment);

        return new ConfirmPaymentResponse(status: $payment->status(), changed: true);
    }
}
