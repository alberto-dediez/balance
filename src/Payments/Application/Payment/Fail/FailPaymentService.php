<?php

declare(strict_types=1);

namespace App\Payments\Application\Payment\Fail;

use App\Payments\Domain\Model\Payment\PaymentRepository;

final class FailPaymentService
{
    public function __construct(private PaymentRepository $paymentRepository) {}

    public function __invoke(FailPaymentRequest $request): FailPaymentResponse
    {
        $payment = $this->paymentRepository->find($request->paymentId);

        if (null === $payment) {
            throw new \InvalidArgumentException('Payment not found');
        }

        if (!$payment->isPending()) {
            return new FailPaymentResponse(status: $payment->status(), changed: false);
        }

        $payment->fail($request->reason);
        $this->paymentRepository->add($payment);

        return new FailPaymentResponse(status: $payment->status(), changed: true);
    }
}
