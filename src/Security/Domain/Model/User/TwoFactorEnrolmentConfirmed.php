<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\User;

use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class TwoFactorEnrolmentConfirmed extends DomainEvent
{
    protected static string $eventName = 'twoFactorEnrolmentConfirmed';

    public function __construct(
        private readonly UserId $userId,
        private readonly TwoFactorMethod $method,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function method(): TwoFactorMethod
    {
        return $this->method;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->userId;
    }

    /**
     * @return array{userId: string, method: string}
     */
    public function payload(): array
    {
        return [
            'userId' => (string) $this->userId,
            'method' => $this->method->value,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new TwoFactorEnrolmentConfirmed(
            userId: new UserId($payload['userId'] ?? ''),
            method: TwoFactorMethod::tryFrom($payload['method'] ?? '') ?? TwoFactorMethod::Email,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
