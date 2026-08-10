<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Payment\Stripe;

use App\Payments\Domain\Model\Payment\PaymentCheckout;
use App\Payments\Domain\Model\Payment\PaymentGateway;
use App\Payments\Domain\Model\Payment\PaymentGatewayException;
use App\Payments\Domain\Model\Payment\PaymentRedirect;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'dev')]
#[When(env: 'prod')]
final class StripePaymentGateway implements PaymentGateway
{
    public const NAME = 'stripe';

    private ?StripeClient $client = null;

    public function __construct(private string $secretKey) {}

    public function name(): string
    {
        return self::NAME;
    }

    public function start(PaymentCheckout $checkout): PaymentRedirect
    {
        try {
            $session = $this->client()->checkout->sessions->create($this->parameters($checkout));
        } catch (ApiErrorException $exception) {
            throw new PaymentGatewayException(sprintf('Stripe could not create the checkout session: %s', $exception->getMessage()), $exception);
        }

        $redirectUrl = $session->url;

        if (null === $redirectUrl) {
            throw new PaymentGatewayException('Stripe returned a checkout session without a redirect URL.');
        }

        return new PaymentRedirect(externalId: $session->id, redirectUrl: $redirectUrl);
    }

    /** @return array<string, mixed> */
    private function parameters(PaymentCheckout $checkout): array
    {
        $parameters = [
            'mode' => 'payment',
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($checkout->currency),
                    'unit_amount' => $checkout->amountInCents(),
                    'product_data' => ['name' => $checkout->description],
                ],
            ]],
            'customer_email' => $checkout->customerEmail,
            'success_url' => $checkout->successUrl,
            'cancel_url' => $checkout->cancelUrl,
            'metadata' => $checkout->metadata,
            'payment_intent_data' => ['metadata' => $checkout->metadata],
        ];

        if ($checkout->expiresAt instanceof \DateTimeImmutable) {
            $parameters['expires_at'] = $checkout->expiresAt->getTimestamp();
        }

        return $parameters;
    }

    private function client(): StripeClient
    {
        if (null === $this->client) {
            if ('' === trim($this->secretKey)) {
                throw new PaymentGatewayException('STRIPE_SECRET_KEY is not configured.');
            }

            $this->client = new StripeClient($this->secretKey);
        }

        return $this->client;
    }
}
