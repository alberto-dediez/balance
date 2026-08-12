<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\UI\Web\Public;

use App\Payments\Application\Payment\Confirm\ConfirmPaymentRequest;
use App\Payments\Application\Payment\Confirm\ConfirmPaymentService;
use App\Payments\Application\Payment\Expire\ExpirePaymentRequest;
use App\Payments\Application\Payment\Expire\ExpirePaymentService;
use App\Payments\Application\Payment\Fail\FailPaymentRequest;
use App\Payments\Application\Payment\Fail\FailPaymentService;
use App\Payments\Domain\Model\Payment\Payment;
use App\Payments\Domain\Model\Payment\PaymentId;
use App\Payments\Domain\Model\Payment\PaymentRepository;
use App\Payments\Infrastructure\Payment\Stripe\StripePaymentGateway;
use App\Payments\Infrastructure\Payment\Stripe\StripeWebhookVerifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/webhooks/stripe', name: 'payments_stripe_webhook', methods: ['POST'], stateless: true)]
class StripeWebhookController extends AbstractController
{
    public function __construct(
        private StripeWebhookVerifier $verifier,
        private PaymentRepository $paymentRepository,
        private ConfirmPaymentService $confirmPayment,
        private FailPaymentService $failPayment,
        private ExpirePaymentService $expirePayment,
    ) {}

    public function __invoke(Request $request): Response
    {
        $event = $this->verifier->verify($request->getContent(), $request->headers->get('Stripe-Signature'));

        if (null === $event) {
            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        /** @var array<string, mixed> $object */
        $object = $event->data->object->toArray();
        $payment = $this->payment($object);

        if (!$payment instanceof Payment) {
            return new Response('Ignored', Response::HTTP_OK);
        }

        $paymentId = $payment->id();

        match ($event->type) {
            'checkout.session.completed' => $this->confirmPayment->__invoke(
                new ConfirmPaymentRequest($paymentId, $this->externalPaymentId($object)),
            ),
            'checkout.session.expired' => $this->expirePayment->__invoke(new ExpirePaymentRequest($paymentId)),
            'payment_intent.payment_failed' => $this->failPayment->__invoke(
                new FailPaymentRequest($paymentId, $this->failureReason($object)),
            ),
            default => null,
        };

        return new Response('OK', Response::HTTP_OK);
    }

    /**
     * @param array<string, mixed> $object
     */
    private function payment(array $object): ?Payment
    {
        $payment = $this->findPayment($object);

        if (null === $payment || StripePaymentGateway::NAME !== $payment->gateway()) {
            return null;
        }

        return $payment;
    }

    /** @param array<string, mixed> $object */
    private function findPayment(array $object): ?Payment
    {
        /** @var array<string, mixed> $metadata */
        $metadata = is_array($object['metadata'] ?? null) ? $object['metadata'] : [];
        $id = $metadata['payment_id'] ?? null;

        if (is_string($id) && '' !== $id) {
            return $this->paymentRepository->find(new PaymentId($id));
        }

        $externalId = $object['id'] ?? null;

        if (!is_string($externalId)) {
            return null;
        }

        return $this->paymentRepository->findOneByExternalId($externalId);
    }

    /** @param array<string, mixed> $object */
    private function externalPaymentId(array $object): ?string
    {
        $paymentIntent = $object['payment_intent'] ?? null;

        return is_string($paymentIntent) ? $paymentIntent : null;
    }

    /** @param array<string, mixed> $object */
    private function failureReason(array $object): string
    {
        /** @var array<string, mixed> $lastPaymentError */
        $lastPaymentError = is_array($object['last_payment_error'] ?? null) ? $object['last_payment_error'] : [];
        $message = $lastPaymentError['message'] ?? null;

        return is_string($message) ? $message : 'payment_failed';
    }
}
