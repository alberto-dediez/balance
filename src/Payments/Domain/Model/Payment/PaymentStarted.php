<?php

declare(strict_types=1);

namespace App\Payments\Domain\Model\Payment;

use App\Payments\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PaymentStarted extends DomainEvent
{
    protected static string $eventName = 'paymentStarted';

    public function __construct(
        private PaymentId $paymentId,
        private string $subjectType,
        private string $subjectId,
        private string $description,
        private float $amount,
        private string $currency,
        private string $customerEmail,
        private string $customerName,
        private string $gateway,
        private string $externalId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function paymentId(): PaymentId
    {
        return $this->paymentId;
    }

    public function subjectType(): string
    {
        return $this->subjectType;
    }

    public function subjectId(): string
    {
        return $this->subjectId;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function customerEmail(): string
    {
        return $this->customerEmail;
    }

    public function customerName(): string
    {
        return $this->customerName;
    }

    public function gateway(): string
    {
        return $this->gateway;
    }

    public function externalId(): string
    {
        return $this->externalId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->paymentId();
    }

    public function payload(): array
    {
        return [
            'paymentId' => (string) $this->paymentId,
            'subjectType' => $this->subjectType,
            'subjectId' => $this->subjectId,
            'description' => $this->description,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'customerEmail' => $this->customerEmail,
            'customerName' => $this->customerName,
            'gateway' => $this->gateway,
            'externalId' => $this->externalId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PaymentStarted(
            paymentId: new PaymentId($payload['paymentId']),
            subjectType: (string) $payload['subjectType'],
            subjectId: (string) $payload['subjectId'],
            description: (string) $payload['description'],
            amount: (float) $payload['amount'],
            currency: (string) $payload['currency'],
            customerEmail: (string) $payload['customerEmail'],
            customerName: (string) $payload['customerName'],
            gateway: (string) $payload['gateway'],
            externalId: (string) $payload['externalId'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
