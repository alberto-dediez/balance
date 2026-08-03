<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\Project;

use App\Library\Domain\Model\Media\MediaId;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;

class Project implements Translatable
{
    use AggregateRoot;

    /** @var iterable<ProjectTranslation>|Translations */
    private iterable $translations;

    /**
     * @param array<string> $gallery
     * @param array<mixed>  $events
     */
    public function __construct(
        private ProjectId $id,
        private string $name = '',
        private string $description = '',
        private ?DestinationId $location = null,
        private array $gallery = [],
        private ?ProjectType $projectType = null,
        private ?ProjectStatus $projectStatus = null,
        private ?string $mitecoId = null,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();
        $this->events = $events;
    }

    public function id(): ProjectId
    {
        return $this->id;
    }

    public function name(): string
    {
        /** @var ProjectTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->name() : $this->name;
    }

    public function description(): string
    {
        /** @var ProjectTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->description() : $this->description;
    }

    public function location(): ?DestinationId
    {
        return $this->location;
    }

    /** @return array<MediaId> */
    public function gallery(): array
    {
        return array_map(static fn(string $mediaId): MediaId => new MediaId($mediaId), $this->gallery);
    }

    public function projectType(): ?ProjectType
    {
        return $this->projectType;
    }

    public function projectStatus(): ?ProjectStatus
    {
        return $this->projectStatus;
    }

    public function mitecoId(): ?string
    {
        return $this->mitecoId;
    }

    public function translations(): Translations
    {
        if ($this->translations instanceof Translations) {
            return $this->translations;
        }

        $this->translations = new Translations(iterator_to_array($this->translations));

        return $this->translations;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    /** @param array<MediaId> $gallery */
    public function register(
        string $name,
        string $description,
        ?DestinationId $location,
        array $gallery,
        ?ProjectType $projectType,
        ?ProjectStatus $projectStatus,
        ?string $mitecoId,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new ProjectRegistered(
                projectId: $this->id(),
                name: $name,
                description: $description,
                location: $location,
                gallery: $gallery,
                projectType: $projectType,
                projectStatus: $projectStatus,
                mitecoId: $mitecoId,
            ),
        );

        if (null !== $locale) {
            $this->translate($name, $description, $locale);
        }
    }

    /** @param array<MediaId> $gallery */
    public function update(
        string $name,
        string $description,
        ?DestinationId $location,
        array $gallery,
        ?ProjectType $projectType,
        ?ProjectStatus $projectStatus,
        ?string $mitecoId,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new ProjectUpdated(
                projectId: $this->id(),
                name: null === $locale ? $name : $this->name,
                description: null === $locale ? $description : $this->description,
                location: $location,
                gallery: $gallery,
                projectType: $projectType,
                projectStatus: $projectStatus,
                mitecoId: $mitecoId,
            ),
        );

        if (null !== $locale) {
            $this->translate($name, $description, $locale);
        }
    }

    public function translate(string $name, string $description, Locale $locale): void
    {
        $this->recordApplyAndPublish(
            new ProjectTranslated(
                projectId: $this->id(),
                name: $name,
                description: $description,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new ProjectRemoved(
                projectId: $this->id(),
            ),
        );
    }

    public function applyProjectRegistered(ProjectRegistered $event): void
    {
        $this->name = $event->name();
        $this->description = $event->description();
        $this->location = $event->location();
        $this->gallery = array_map(static fn(MediaId $mediaId): string => (string) $mediaId, $event->gallery());
        $this->projectType = $event->projectType();
        $this->projectStatus = $event->projectStatus();
        $this->mitecoId = $event->mitecoId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyProjectUpdated(ProjectUpdated $event): void
    {
        $this->name = $event->name();
        $this->description = $event->description();
        $this->location = $event->location();
        $this->gallery = array_map(static fn(MediaId $mediaId): string => (string) $mediaId, $event->gallery());
        $this->projectType = $event->projectType();
        $this->projectStatus = $event->projectStatus();
        $this->mitecoId = $event->mitecoId();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyProjectTranslated(ProjectTranslated $event): void
    {
        $projectTranslation = new ProjectTranslation(
            name: $event->name(),
            description: $event->description(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add($projectTranslation);
    }

    public function applyProjectRemoved(ProjectRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }
}
