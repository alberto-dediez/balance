<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payments\Application\Payment\Start;

use App\Payments\Application\Payment\Start\StartPaymentRequest;
use App\Payments\Application\Payment\Start\StartPaymentService;
use App\Payments\Domain\Model\Payment\PaymentStatus;
use App\Payments\Infrastructure\Payment\Fake\FakePaymentGateway;
use App\Payments\Infrastructure\Persistence\InMemory\Repository\InMemoryPaymentRepository;
use App\Tests\Unit\Payments\Domain\Model\Payment\PaymentRepositoryMother;
use PHPUnit\Framework\TestCase;

class StartPaymentServiceTest extends TestCase
{
    private InMemoryPaymentRepository $payments;
    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        $this->payments = PaymentRepositoryMother::inMemory();
        $this->gateway = new FakePaymentGateway();
    }

    public function testStartsThePaymentAndStoresItAsPending(): void
    {
        $response = $this->createSUT()($this->request());

        $payment = $this->payments->find($response->paymentId);

        $this->assertNotNull($payment);
        $this->assertSame(PaymentStatus::Pending, $payment->status());
        $this->assertSame(48.4, $payment->amount());
        $this->assertSame('EUR', $payment->currency());
        $this->assertSame('stripe', $payment->gateway());
        $this->assertSame($response->externalId, $payment->externalId());
    }

    public function testReturnsTheGatewayRedirect(): void
    {
        $response = $this->createSUT()($this->request());

        $this->assertStringStartsWith('https://checkout.stripe.test/', $response->redirectUrl);
        $this->assertStringStartsWith('cs_test_', $response->externalId);
    }

    public function testTheCheckoutCarriesTheSubjectAsMetadata(): void
    {
        $response = $this->createSUT()($this->request());

        $checkout = $this->gateway->lastCheckout();

        $this->assertNotNull($checkout);
        $this->assertSame((string) $response->paymentId, $checkout->metadata['payment_id']);
        $this->assertSame('balance.project_contribution', $checkout->metadata['subject_type']);
        $this->assertSame('contribution-1', $checkout->metadata['subject_id']);
        $this->assertSame('order', $checkout->metadata['origin']);
        $this->assertSame('https://app.test/return', $checkout->successUrl);
        $this->assertSame('https://app.test/cancel', $checkout->cancelUrl);
    }

    public function testTheCheckoutKeepsTheExpirationOfTheReservation(): void
    {
        $expiresAt = new \DateTimeImmutable('2026-08-06 12:00:00');

        $this->createSUT()($this->request(expiresAt: $expiresAt));

        $checkout = $this->gateway->lastCheckout();

        $this->assertNotNull($checkout);
        $this->assertEquals($expiresAt, $checkout->expiresAt);
    }

    public function testRejectsANonPositiveAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createSUT()($this->request(amount: 0.0));
    }

    private function request(
        float $amount = 48.4,
        ?\DateTimeImmutable $expiresAt = null,
    ): StartPaymentRequest {
        return new StartPaymentRequest(
            subjectType: 'balance.project_contribution',
            subjectId: 'contribution-1',
            description: 'Aportación al proyecto',
            amount: $amount,
            customerEmail: 'guest@example.com',
            customerName: 'Guest',
            successUrl: 'https://app.test/return',
            cancelUrl: 'https://app.test/cancel',
            metadata: ['origin' => 'order'],
            expiresAt: $expiresAt,
        );
    }

    private function createSUT(): StartPaymentService
    {
        return new StartPaymentService($this->payments, $this->gateway);
    }
}
