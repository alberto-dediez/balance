<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\ApiToken;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class ApiToken implements \Stringable
{
    use AggregateRoot;

    public const MAX_ACTIVE_PER_USER = 10;
    private const NAME_MAX_LENGTH = 100;

    /**
     * @param DomainEvent[] $events
     */
    public function __construct(
        private ApiTokenId $id,
        private ?UserId $userId = null,
        private ?AccountId $accountId = null,
        private string $name = '',
        private string $tokenHash = '',
        private string $tokenPrefix = '',
        private ?\DateTimeImmutable $expiresAt = null,
        private ?\DateTimeImmutable $lastUsedAt = null,
        private ?\DateTimeImmutable $revokedAt = null,
        private ?UserId $revokedBy = null,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
    ) {
        $this->events = $events;
    }

    public function id(): ApiTokenId
    {
        return $this->id;
    }

    public function userId(): ?UserId
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

    public function lastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function revokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function revokedBy(): ?UserId
    {
        return $this->revokedBy;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function register(
        UserId $userId,
        ?AccountId $accountId,
        string $name,
        ApiTokenSecret $secret,
        ?\DateTimeImmutable $expiresAt,
        \DateTimeImmutable $now,
    ): void {
        if (null !== $expiresAt && $expiresAt <= $now) {
            throw new ApiTokenExpirationInvalidException();
        }

        $name = trim($name);

        if ('' === $name || mb_strlen($name) > self::NAME_MAX_LENGTH) {
            throw new \InvalidArgumentException('Invalid api token name');
        }

        $this->recordApplyAndPublish(
            new ApiTokenRegistered(
                apiTokenId: $this->id(),
                userId: $userId,
                accountId: $accountId,
                name: $name,
                tokenHash: $secret->hash(),
                tokenPrefix: $secret->prefix(),
                expiresAt: $expiresAt,
                occurredOn: $now,
            ),
        );
    }

    public function markAsUsed(\DateTimeImmutable $now): void
    {
        $this->recordApplyAndPublish(
            new ApiTokenUsed(
                apiTokenId: $this->id(),
                userId: $this->userId(),
                occurredOn: $now,
            ),
        );
    }

    public function revoke(UserId $revokedBy, \DateTimeImmutable $now): void
    {
        if ($this->isRevoked()) {
            return;
        }

        $this->recordApplyAndPublish(
            new ApiTokenRevoked(
                apiTokenId: $this->id(),
                userId: $this->userId(),
                revokedBy: $revokedBy,
                occurredOn: $now,
            ),
        );
    }

    public function status(\DateTimeImmutable $now): ApiTokenStatus
    {
        if ($this->isRevoked()) {
            return ApiTokenStatus::Revoked;
        }

        if (null !== $this->expiresAt && $this->expiresAt <= $now) {
            return ApiTokenStatus::Expired;
        }

        return ApiTokenStatus::Active;
    }

    public function currentStatus(): ApiTokenStatus
    {
        return $this->status(new \DateTimeImmutable('NOW'));
    }

    public function isActive(\DateTimeImmutable $now): bool
    {
        return ApiTokenStatus::Active === $this->status($now);
    }

    public function isRevoked(): bool
    {
        return null !== $this->revokedAt;
    }

    public function belongsTo(UserId $userId): bool
    {
        return null !== $this->userId && $this->userId->equals($userId);
    }

    public function applyApiTokenRegistered(ApiTokenRegistered $event): void
    {
        $this->userId = $event->userId();
        $this->accountId = $event->accountId();
        $this->name = $event->name();
        $this->tokenHash = $event->tokenHash();
        $this->tokenPrefix = $event->tokenPrefix();
        $this->expiresAt = $event->expiresAt();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyApiTokenUsed(ApiTokenUsed $event): void
    {
        $this->lastUsedAt = $event->occurredOn();
    }

    public function applyApiTokenRevoked(ApiTokenRevoked $event): void
    {
        $this->revokedAt = $event->occurredOn();
        $this->revokedBy = $event->revokedBy();
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
