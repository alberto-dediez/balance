<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Payment\Stripe;

use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

final class StripeWebhookVerifier
{
    public function __construct(private string $webhookSecret) {}

    public function verify(string $payload, ?string $signature): ?Event
    {
        if (null === $signature || '' === trim($this->webhookSecret)) {
            return null;
        }

        try {
            return Webhook::constructEvent($payload, $signature, $this->webhookSecret);
        } catch (SignatureVerificationException|\UnexpectedValueException) {
            return null;
        }
    }
}
