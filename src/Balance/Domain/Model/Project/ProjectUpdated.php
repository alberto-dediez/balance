<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\Project;

use App\Balance\Domain\DomainEvent;
use App\Library\Domain\Model\Media\MediaId;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ProjectUpdated extends DomainEvent
{
    protected static string $eventName = 'projectUpdated';

    /** @param array<MediaId> $gallery */
    public function __construct(
        private ProjectId $projectId,
        private string $name,
        private string $description,
        private ?DestinationId $location,
        private array $gallery,
        private ?ProjectType $projectType,
        private ?ProjectStatus $projectStatus,
        private ?string $mitecoId,
        private ?float $pricePerTonne = null,
        private ?float $costPerTonne = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function projectId(): ProjectId
    {
        return $this->projectId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function location(): ?DestinationId
    {
        return $this->location;
    }

    /** @return array<MediaId> */
    public function gallery(): array
    {
        return $this->gallery;
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

    public function pricePerTonne(): ?float
    {
        return $this->pricePerTonne;
    }

    public function costPerTonne(): ?float
    {
        return $this->costPerTonne;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->projectId();
    }

    public function payload(): array
    {
        return [
            'projectId' => (string) $this->projectId,
            'name' => $this->name,
            'description' => $this->description,
            'location' => null !== $this->location ? (string) $this->location : null,
            'gallery' => array_map(static fn(MediaId $mediaId): string => (string) $mediaId, $this->gallery),
            'projectType' => $this->projectType?->value,
            'projectStatus' => $this->projectStatus?->value,
            'mitecoId' => $this->mitecoId,
            'pricePerTonne' => $this->pricePerTonne,
            'costPerTonne' => $this->costPerTonne,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $projectType = null !== $payload['projectType'] ? ProjectType::tryFrom($payload['projectType']) : null;
        $projectStatus = null !== $payload['projectStatus'] ? ProjectStatus::tryFrom($payload['projectStatus']) : null;

        return new ProjectUpdated(
            projectId: new ProjectId($payload['projectId']),
            name: $payload['name'],
            description: $payload['description'],
            location: null !== $payload['location'] ? new DestinationId($payload['location']) : null,
            gallery: array_map(static fn(string $mediaId): MediaId => new MediaId($mediaId), $payload['gallery']),
            projectType: $projectType,
            projectStatus: $projectStatus,
            mitecoId: $payload['mitecoId'],
            pricePerTonne: isset($payload['pricePerTonne']) ? (float) $payload['pricePerTonne'] : null,
            costPerTonne: isset($payload['costPerTonne']) ? (float) $payload['costPerTonne'] : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
