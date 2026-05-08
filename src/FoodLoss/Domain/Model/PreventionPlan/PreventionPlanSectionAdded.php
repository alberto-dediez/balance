<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class PreventionPlanSectionAdded extends DomainEvent
{
    protected static string $eventName = 'preventionPlanSectionAdded';

    public function __construct(
        private readonly PreventionPlanId $preventionPlanId,
        private readonly int $versionNumber,
        private readonly PreventionPlanSectionId $sectionId,
        private readonly string $title,
        private readonly string $content,
        private readonly int $sectionOrder = 0,
        private readonly Locale $locale = Locale::Spanish,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function preventionPlanId(): PreventionPlanId
    {
        return $this->preventionPlanId;
    }

    public function versionNumber(): int
    {
        return $this->versionNumber;
    }

    public function sectionId(): PreventionPlanSectionId
    {
        return $this->sectionId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function sectionOrder(): int
    {
        return $this->sectionOrder;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->preventionPlanId;
    }

    public function payload(): array
    {
        return [
            'preventionPlanId' => (string) $this->preventionPlanId,
            'versionNumber' => $this->versionNumber,
            'sectionId' => (string) $this->sectionId,
            'title' => $this->title,
            'content' => $this->content,
            'sectionOrder' => $this->sectionOrder,
            'locale' => $this->locale->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PreventionPlanSectionAdded(
            preventionPlanId: new PreventionPlanId($payload['preventionPlanId']),
            versionNumber: (int) $payload['versionNumber'],
            sectionId: new PreventionPlanSectionId($payload['sectionId']),
            title: $payload['title'],
            content: $payload['content'],
            sectionOrder: (int) ($payload['sectionOrder'] ?? 0),
            locale: Locale::tryFrom($payload['locale'] ?? 'es') ?? Locale::Spanish,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
