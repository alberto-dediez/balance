<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Infrastructure\Payment\Stripe;

use App\Payments\Infrastructure\Payment\Stripe\StripeWebhookVerifier;
use PHPUnit\Framework\TestCase;

class StripeWebhookVerifierTest extends TestCase
{
    private const SECRET = 'whsec_test_secret';

    public function testVerifiesAWellSignedPayload(): void
    {
        $payload = $this->payload();

        $event = (new StripeWebhookVerifier(self::SECRET))->verify($payload, $this->signature($payload));

        $this->assertNotNull($event);
        $this->assertSame('checkout.session.completed', $event->type);
    }

    public function testRejectsAPayloadSignedWithAnotherSecret(): void
    {
        $payload = $this->payload();

        $event = (new StripeWebhookVerifier(self::SECRET))->verify(
            $payload,
            $this->signature($payload, 'whsec_another_secret'),
        );

        $this->assertNull($event);
    }

    public function testRejectsATamperedPayload(): void
    {
        $signature = $this->signature($this->payload());

        $event = (new StripeWebhookVerifier(self::SECRET))->verify($this->payload('evt_tampered'), $signature);

        $this->assertNull($event);
    }

    public function testRejectsAnUnsignedRequest(): void
    {
        $this->assertNull((new StripeWebhookVerifier(self::SECRET))->verify($this->payload(), null));
    }

    public function testRejectsEverythingWhenNoSecretIsConfigured(): void
    {
        $payload = $this->payload();

        $this->assertNull((new StripeWebhookVerifier(''))->verify($payload, $this->signature($payload, '')));
    }

    private function payload(string $eventId = 'evt_test_1'): string
    {
        return (string) json_encode([
            'id' => $eventId,
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test_1', 'object' => 'checkout.session']],
        ]);
    }

    private function signature(string $payload, string $secret = self::SECRET): string
    {
        $timestamp = time();

        return sprintf(
            't=%d,v1=%s',
            $timestamp,
            hash_hmac('sha256', sprintf('%d.%s', $timestamp, $payload), $secret),
        );
    }
}
