<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\ApiToken;

use App\Security\Domain\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ApiTokenUsed extends DomainEvent
{
    protected static string $eventName = 'apiTokenUsed';

    public function __construct(
        private readonly ApiTokenId $apiTokenId,
        private readonly ?UserId $userId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function apiTokenId(): ApiTokenId
    {
        return $this->apiTokenId;
    }

    public function userId(): ?UserId
    {
        return $this->userId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->apiTokenId;
    }

    public function payload(): array
    {
        return [
            'apiTokenId' => (string) $this->apiTokenId,
            'userId' => null !== $this->userId ? (string) $this->userId : null,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ApiTokenUsed(
            apiTokenId: new ApiTokenId($payload['apiTokenId']),
            userId: isset($payload['userId']) ? new UserId($payload['userId']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
