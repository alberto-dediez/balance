<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Payment\Fake;

use App\Payments\Domain\Model\Payment\PaymentCheckout;
use App\Payments\Domain\Model\Payment\PaymentGateway;
use App\Payments\Domain\Model\Payment\PaymentRedirect;
use App\Payments\Infrastructure\Payment\Stripe\StripePaymentGateway;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * Stand-in for {@see StripePaymentGateway} in tests: it never reaches Stripe and returns a
 * deterministic checkout session id and URL. It reuses the Stripe gateway name so the payments
 * recorded in tests go through the same webhook path as the real ones.
 */
#[When(env: 'test')]
final class FakePaymentGateway implements PaymentGateway
{
    /** @var array<int, PaymentCheckout> */
    private array $checkouts = [];

    public function name(): string
    {
        return StripePaymentGateway::NAME;
    }

    public function start(PaymentCheckout $checkout): PaymentRedirect
    {
        $this->checkouts[] = $checkout;

        $externalId = sprintf('cs_test_%s', str_replace('-', '', (string) $checkout->paymentId));

        return new PaymentRedirect(
            externalId: $externalId,
            redirectUrl: sprintf('https://checkout.stripe.test/%s', $externalId),
        );
    }

    /** @return array<int, PaymentCheckout> */
    public function checkouts(): array
    {
        return $this->checkouts;
    }

    public function lastCheckout(): ?PaymentCheckout
    {
        return [] === $this->checkouts ? null : $this->checkouts[count($this->checkouts) - 1];
    }
}
