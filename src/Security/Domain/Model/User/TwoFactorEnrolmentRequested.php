<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\User;

use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class TwoFactorEnrolmentRequested extends DomainEvent
{
    protected static string $eventName = 'twoFactorEnrolmentRequested';

    public function __construct(
        private readonly UserId $userId,
        private readonly TwoFactorMethod $method,
        #[\SensitiveParameter]
        private readonly string $encryptedSecret,
        private readonly TwoFactorChallenge $challenge,
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

    public function encryptedSecret(): string
    {
        return $this->encryptedSecret;
    }

    public function challenge(): TwoFactorChallenge
    {
        return $this->challenge;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->userId;
    }

    /**
     * @return array{userId: string, method: string, expiresAt: ?string}
     */
    public function payload(): array
    {
        return [
            'userId' => (string) $this->userId,
            'method' => $this->method->value,
            'expiresAt' => $this->challenge->expiresAt()?->format(\DateTimeInterface::ATOM),
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
        return new TwoFactorEnrolmentRequested(
            userId: new UserId($payload['userId'] ?? ''),
            method: TwoFactorMethod::tryFrom($payload['method'] ?? '') ?? TwoFactorMethod::Totp,
            encryptedSecret: '',
            challenge: TwoFactorChallenge::none(),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
