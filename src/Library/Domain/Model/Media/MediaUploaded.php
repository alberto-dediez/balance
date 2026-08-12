<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Media;

use App\Library\Domain\DomainEvent;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MediaUploaded extends DomainEvent
{
    protected static string $eventName = 'mediaUploaded';

    public function __construct(
        private readonly MediaId $mediaId,
        private readonly string $name,
        private readonly string $fileName,
        private readonly string $path,
        private readonly string $extension,
        private readonly string $mimeType,
        private readonly MediaType $type,
        private readonly MediaVisibility $visibility,
        private readonly bool $shared,
        private readonly int $size,
        private readonly ?AccountId $accountId = null,
        private readonly ?UserId $uploadedBy = null,
        private readonly ?string $description = null,
        private readonly ?string $alternativeText = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function mediaId(): MediaId
    {
        return $this->mediaId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function fileName(): string
    {
        return $this->fileName;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function extension(): string
    {
        return $this->extension;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function type(): MediaType
    {
        return $this->type;
    }

    public function visibility(): MediaVisibility
    {
        return $this->visibility;
    }

    public function isShared(): bool
    {
        return $this->shared;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function uploadedBy(): ?UserId
    {
        return $this->uploadedBy;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function alternativeText(): ?string
    {
        return $this->alternativeText;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->mediaId;
    }

    public function payload(): array
    {
        return [
            'mediaId' => (string) $this->mediaId,
            'name' => $this->name,
            'fileName' => $this->fileName,
            'path' => $this->path,
            'extension' => $this->extension,
            'mimeType' => $this->mimeType,
            'type' => $this->type->value,
            'visibility' => $this->visibility->value,
            'shared' => $this->shared,
            'size' => $this->size,
            'accountId' => $this->accountId instanceof AccountId ? (string) $this->accountId : null,
            'uploadedBy' => $this->uploadedBy instanceof UserId ? (string) $this->uploadedBy : null,
            'description' => $this->description,
            'alternativeText' => $this->alternativeText,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MediaUploaded(
            mediaId: new MediaId($payload['mediaId']),
            name: $payload['name'],
            fileName: $payload['fileName'],
            path: $payload['path'],
            extension: $payload['extension'],
            mimeType: $payload['mimeType'],
            type: MediaType::from($payload['type']),
            visibility: MediaVisibility::from($payload['visibility'] ?? MediaVisibility::Account->value),
            shared: (bool) ($payload['shared'] ?? false),
            size: (int) $payload['size'],
            accountId: null === $payload['accountId'] ? null : new AccountId($payload['accountId']),
            uploadedBy: null === $payload['uploadedBy'] ? null : new UserId($payload['uploadedBy']),
            description: $payload['description'],
            alternativeText: $payload['alternativeText'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
