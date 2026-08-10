<?php

declare(strict_types=1);

namespace App\Payments\Application\Payment\Expire;

use App\Payments\Domain\Model\Payment\PaymentRepository;

final class ExpirePaymentService
{
    public function __construct(private PaymentRepository $paymentRepository) {}

    public function __invoke(ExpirePaymentRequest $request): ExpirePaymentResponse
    {
        $payment = $this->paymentRepository->find($request->paymentId);

        if (null === $payment) {
            throw new \InvalidArgumentException('Payment not found');
        }

        if (!$payment->isPending()) {
            return new ExpirePaymentResponse(status: $payment->status(), changed: false);
        }

        $payment->expire();
        $this->paymentRepository->add($payment);

        return new ExpirePaymentResponse(status: $payment->status(), changed: true);
    }
}
