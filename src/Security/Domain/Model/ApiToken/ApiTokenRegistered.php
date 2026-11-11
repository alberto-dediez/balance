<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\ApiToken;

use App\Security\Domain\DomainEvent;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ApiTokenRegistered extends DomainEvent
{
    protected static string $eventName = 'apiTokenRegistered';

    public function __construct(
        private readonly ApiTokenId $apiTokenId,
        private readonly UserId $userId,
        private readonly ?AccountId $accountId,
        private readonly string $name,
        private readonly string $tokenHash,
        private readonly string $tokenPrefix,
        private readonly ?\DateTimeImmutable $expiresAt,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function apiTokenId(): ApiTokenId
    {
        return $this->apiTokenId;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function tokenHash(): string
    {
        return $this->tokenHash;
    }

    public function tokenPrefix(): string
    {
        return $this->tokenPrefix;
    }

    public function expiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->apiTokenId;
    }

    public function payload(): array
    {
        return [
            'apiTokenId' => (string) $this->apiTokenId,
            'userId' => (string) $this->userId,
            'accountId' => null !== $this->accountId ? (string) $this->accountId : null,
            'name' => $this->name,
            'tokenPrefix' => $this->tokenPrefix,
            'expiresAt' => $this->expiresAt?->format(\DateTimeInterface::ATOM),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ApiTokenRegistered(
            apiTokenId: new ApiTokenId($payload['apiTokenId']),
            userId: new UserId($payload['userId']),
            accountId: isset($payload['accountId']) ? new AccountId($payload['accountId']) : null,
            name: $payload['name'],
            tokenHash: '',
            tokenPrefix: $payload['tokenPrefix'],
            expiresAt: isset($payload['expiresAt']) ? new \DateTimeImmutable($payload['expiresAt']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
