<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\User;

use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class PreferredLocaleChanged extends DomainEvent
{
    protected static string $eventName = 'preferredLocaleChanged';

    public function __construct(
        private readonly UserId $userId,
        private readonly Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->userId;
    }

    /**
     * @return array{userId: string, locale: string}
     */
    public function payload(): array
    {
        return [
            'userId' => (string) $this->userId,
            'locale' => $this->locale->value,
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
        return new PreferredLocaleChanged(
            userId: new UserId($payload['userId'] ?? ''),
            locale: Locale::from($payload['locale'] ?? 'es'),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
