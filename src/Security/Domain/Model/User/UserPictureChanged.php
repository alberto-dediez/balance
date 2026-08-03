<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\User;

use App\Library\Domain\Model\Media\MediaId;
use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class UserPictureChanged extends DomainEvent
{
    protected static string $eventName = 'userPictureChanged';

    public function __construct(
        private readonly UserId $userId,
        private readonly ?MediaId $pictureId = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function pictureId(): ?MediaId
    {
        return $this->pictureId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->userId;
    }

    /**
     * @return array{userId: string, pictureId: ?string}
     */
    public function payload(): array
    {
        return [
            'userId' => (string) $this->userId,
            'pictureId' => $this->pictureId instanceof MediaId ? (string) $this->pictureId : null,
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
        return new UserPictureChanged(
            userId: new UserId($payload['userId'] ?? ''),
            pictureId: null === ($payload['pictureId'] ?? null) ? null : new MediaId($payload['pictureId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
