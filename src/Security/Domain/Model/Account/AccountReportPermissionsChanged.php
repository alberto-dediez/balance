<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Account;

use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class AccountReportPermissionsChanged extends DomainEvent
{
    protected static string $eventName = 'accountReportPermissionsChanged';

    public function __construct(
        private readonly AccountId $accountId,
        private readonly bool $canEditTexts,
        private readonly bool $autoReview,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function canEditTexts(): bool
    {
        return $this->canEditTexts;
    }

    public function autoReview(): bool
    {
        return $this->autoReview;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->accountId;
    }

    public function payload(): array
    {
        return [
            'accountId' => (string) $this->accountId,
            'canEditTexts' => $this->canEditTexts,
            'autoReview' => $this->autoReview,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        assert(is_bool($payload['canEditTexts']) && is_bool($payload['autoReview']));

        return new self(
            accountId: new AccountId($payload['accountId']),
            canEditTexts: $payload['canEditTexts'],
            autoReview: $payload['autoReview'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
