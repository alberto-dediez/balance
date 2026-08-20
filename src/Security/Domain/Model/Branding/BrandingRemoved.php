<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Branding;

use App\Security\Domain\DomainEvent;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class BrandingRemoved extends DomainEvent
{
    protected static string $eventName = 'brandingRemoved';

    public function __construct(
        private readonly BrandingId $brandingId,
        private readonly AccountId $accountId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function brandingId(): BrandingId
    {
        return $this->brandingId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->brandingId;
    }

    public function payload(): array
    {
        return [
            'brandingId' => (string) $this->brandingId,
            'accountId' => (string) $this->accountId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new BrandingRemoved(
            brandingId: new BrandingId($payload['brandingId']),
            accountId: new AccountId($payload['accountId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
