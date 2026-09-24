<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\User;

use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class TwoFactorChallengeRequested extends DomainEvent
{
    protected static string $eventName = 'twoFactorChallengeRequested';

    public function __construct(
        private readonly UserId $userId,
        private readonly TwoFactorMethod $method,
        private readonly TwoFactorChallenge $challenge,
        private readonly string $maskedDestination,
        private readonly ?\DateTimeImmutable $expiresAt,
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

    public function challenge(): TwoFactorChallenge
    {
        return $this->challenge;
    }

    public function maskedDestination(): string
    {
        return $this->maskedDestination;
    }

    public function expiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->userId;
    }

    /**
     * @return array{userId: string, method: string, maskedDestination: string, expiresAt: ?string}
     */
    public function payload(): array
    {
        return [
            'userId' => (string) $this->userId,
            'method' => $this->method->value,
            'maskedDestination' => $this->maskedDestination,
            'expiresAt' => $this->expiresAt?->format(\DateTimeInterface::ATOM),
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
        return new TwoFactorChallengeRequested(
            userId: new UserId($payload['userId'] ?? ''),
            method: TwoFactorMethod::tryFrom($payload['method'] ?? '') ?? TwoFactorMethod::Email,
            challenge: TwoFactorChallenge::none(),
            maskedDestination: $payload['maskedDestination'] ?? '',
            expiresAt: null === ($payload['expiresAt'] ?? null) ? null : new \DateTimeImmutable($payload['expiresAt']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
