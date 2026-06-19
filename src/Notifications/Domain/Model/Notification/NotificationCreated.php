<?php

declare(strict_types=1);

namespace App\Notifications\Domain\Model\Notification;

use App\Notifications\Domain\DomainEvent;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class NotificationCreated extends DomainEvent
{
    protected static string $eventName = 'notificationCreated';

    public function __construct(
        private NotificationId $notificationId,
        private UserId $recipientId,
        private NotificationChannel $channel,
        private string $subject,
        private string $body,
        private ?AccountId $accountId = null,
        private ?UserId $sentBy = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function notificationId(): NotificationId
    {
        return $this->notificationId;
    }

    public function recipientId(): UserId
    {
        return $this->recipientId;
    }

    public function channel(): NotificationChannel
    {
        return $this->channel;
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function sentBy(): ?UserId
    {
        return $this->sentBy;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->notificationId;
    }

    /**
     * @return array{notificationId: string, recipientId: string, channel: string, subject: string, body: string, accountId: ?string, sentBy: ?string}
     */
    public function payload(): array
    {
        return [
            'notificationId' => (string) $this->notificationId,
            'recipientId' => (string) $this->recipientId,
            'channel' => $this->channel->value,
            'subject' => $this->subject,
            'body' => $this->body,
            'accountId' => null !== $this->accountId ? (string) $this->accountId : null,
            'sentBy' => null !== $this->sentBy ? (string) $this->sentBy : null,
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
        $channel = NotificationChannel::tryFrom($payload['channel'] ?? NotificationChannel::Internal->value);
        assert($channel instanceof NotificationChannel);

        return new NotificationCreated(
            notificationId: new NotificationId($payload['notificationId'] ?? ''),
            recipientId: new UserId($payload['recipientId'] ?? ''),
            channel: $channel,
            subject: $payload['subject'] ?? '',
            body: $payload['body'] ?? '',
            accountId: isset($payload['accountId']) ? new AccountId($payload['accountId']) : null,
            sentBy: isset($payload['sentBy']) ? new UserId($payload['sentBy']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
