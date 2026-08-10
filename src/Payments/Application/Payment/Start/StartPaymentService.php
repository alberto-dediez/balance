<?php

declare(strict_types=1);

namespace App\Payments\Application\Payment\Start;

use App\Payments\Domain\Model\Payment\Payment;
use App\Payments\Domain\Model\Payment\PaymentCheckout;
use App\Payments\Domain\Model\Payment\PaymentGateway;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Payments\Domain\Model\Payment\PaymentRepository;

final class StartPaymentService
{
    public function __construct(
        private PaymentRepository $paymentRepository,
        private PaymentGateway $paymentGateway,
    ) {}

    public function __invoke(StartPaymentRequest $request): StartPaymentResponse
    {
        $id = PaymentId::generate();

        $redirect = $this->paymentGateway->start(
            new PaymentCheckout(
                paymentId: $id,
                description: $request->description,
                amount: $request->amount,
                currency: $request->currency,
                customerEmail: $request->customerEmail,
                successUrl: $request->successUrl,
                cancelUrl: $request->cancelUrl,
                metadata: array_merge($request->metadata, [
                    'payment_id' => (string) $id,
                    'subject_type' => $request->subjectType,
                    'subject_id' => $request->subjectId,
                ]),
                expiresAt: $request->expiresAt,
            ),
        );

        $payment = new Payment(id: $id);
        $payment->start(
            subjectType: $request->subjectType,
            subjectId: $request->subjectId,
            description: $request->description,
            amount: $request->amount,
            currency: $request->currency,
            customerEmail: $request->customerEmail,
            customerName: $request->customerName,
            gateway: $this->paymentGateway->name(),
            externalId: $redirect->externalId,
        );

        $this->paymentRepository->add($payment);

        return new StartPaymentResponse(
            paymentId: $payment->id(),
            externalId: $redirect->externalId,
            redirectUrl: $redirect->redirectUrl,
        );
    }
}
