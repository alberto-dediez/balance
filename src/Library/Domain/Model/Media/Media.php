<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Media;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class Media implements \Stringable
{
    use AggregateRoot;

    /**
     * @param DomainEvent[] $events
     */
    public function __construct(
        protected MediaId $id,
        protected ?AccountId $accountId = null,
        protected string $name = '',
        protected string $fileName = '',
        protected string $path = '',
        protected string $extension = '',
        protected string $mimeType = '',
        protected MediaType $type = MediaType::Other,
        protected MediaVisibility $visibility = MediaVisibility::Account,
        protected bool $shared = false,
        protected int $size = 0,
        protected ?string $description = null,
        protected ?string $alternativeText = null,
        protected ?UserId $uploadedBy = null,
        protected bool $removed = false,
        array $events = [],
        protected \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        protected ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): MediaId
    {
        return $this->id;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
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

    /**
     * Shared files are browsable from the library of every account, not only the one
     * owning them. It is a decision of the owner, unrelated to the audience the file
     * is accessible to: an avatar reaches every user without ever being shared.
     */
    public function isShared(): bool
    {
        return $this->shared;
    }

    public function isAccessibleOutsideAccount(): bool
    {
        return $this->visibility->isAccessibleOutsideAccount();
    }

    public function isPublic(): bool
    {
        return $this->visibility->isPublic();
    }

    public function size(): int
    {
        return $this->size;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function alternativeText(): ?string
    {
        return $this->alternativeText;
    }

    public function uploadedBy(): ?UserId
    {
        return $this->uploadedBy;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function isImage(): bool
    {
        return MediaType::Image === $this->type;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function upload(
        string $name,
        string $fileName,
        string $path,
        string $extension,
        string $mimeType,
        int $size,
        ?AccountId $accountId = null,
        ?UserId $uploadedBy = null,
        ?string $description = null,
        ?string $alternativeText = null,
        MediaVisibility $visibility = MediaVisibility::Account,
        bool $shared = false,
    ): void {
        $this->recordApplyAndPublish(
            new MediaUploaded(
                mediaId: $this->id(),
                name: $name,
                fileName: $fileName,
                path: $path,
                extension: $extension,
                mimeType: $mimeType,
                type: MediaType::fromMimeType($mimeType),
                visibility: $shared ? self::visibilityReachingEveryAccount($visibility) : $visibility,
                shared: $shared,
                size: $size,
                accountId: $accountId,
                uploadedBy: $uploadedBy,
                description: $description,
                alternativeText: $alternativeText,
            ),
        );
    }

    public function changeDetails(string $name, ?string $description, ?string $alternativeText): void
    {
        $this->recordApplyAndPublish(
            new MediaDetailsChanged(
                mediaId: $this->id(),
                name: $name,
                description: $description,
                alternativeText: $alternativeText,
            ),
        );
    }

    public function changeVisibility(MediaVisibility $visibility): void
    {
        $this->recordApplyAndPublish(
            new MediaVisibilityChanged(
                mediaId: $this->id(),
                visibility: $visibility,
            ),
        );

        // A file nobody outside the account may open has nothing to do in the library
        // of the other accounts.
        if ($this->isShared() && !$visibility->isAccessibleOutsideAccount()) {
            $this->changeSharing(false);
        }
    }

    /**
     * Publishes the file in the library of every account, or withdraws it from there.
     */
    public function changeSharing(bool $shared): void
    {
        // Sharing a file the other accounts cannot even open would list an unusable
        // file, so the audience follows the decision.
        if ($shared && !$this->isAccessibleOutsideAccount()) {
            $this->changeVisibility(self::visibilityReachingEveryAccount($this->visibility()));
        }

        $this->recordApplyAndPublish(
            new MediaSharingChanged(
                mediaId: $this->id(),
                shared: $shared,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new MediaRemoved(
                mediaId: $this->id(),
                path: $this->path(),
            ),
        );
    }

    public function applyMediaUploaded(MediaUploaded $event): void
    {
        $this->name = $event->name();
        $this->fileName = $event->fileName();
        $this->path = $event->path();
        $this->extension = $event->extension();
        $this->mimeType = $event->mimeType();
        $this->type = $event->type();
        $this->visibility = $event->visibility();
        $this->shared = $event->isShared();
        $this->size = $event->size();
        $this->accountId = $event->accountId();
        $this->uploadedBy = $event->uploadedBy();
        $this->description = $event->description();
        $this->alternativeText = $event->alternativeText();

        $this->registeredAt = $event->occurredOn();
    }

    public function applyMediaDetailsChanged(MediaDetailsChanged $event): void
    {
        $this->name = $event->name();
        $this->description = $event->description();
        $this->alternativeText = $event->alternativeText();
    }

    public function applyMediaVisibilityChanged(MediaVisibilityChanged $event): void
    {
        $this->visibility = $event->visibility();
    }

    public function applyMediaSharingChanged(MediaSharingChanged $event): void
    {
        $this->shared = $event->isShared();
    }

    public function applyMediaRemoved(MediaRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->name();
    }

    /**
     * The narrowest audience letting every account reach the file, keeping a wider one
     * already granted.
     */
    private static function visibilityReachingEveryAccount(MediaVisibility $visibility): MediaVisibility
    {
        return $visibility->satisfies(MediaVisibility::System) ? $visibility : MediaVisibility::System;
    }
}
