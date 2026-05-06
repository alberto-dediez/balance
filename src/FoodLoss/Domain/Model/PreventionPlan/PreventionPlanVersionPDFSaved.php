<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class PreventionPlanVersionPDFSaved extends DomainEvent
{
    protected static string $eventName = 'preventionPlanVersionPDFSaved';

    public function __construct(
        private PreventionPlanId $preventionPlanId,
        private int $versionNumber,
        private string $pdfPath,
        private Locale $locale,
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

    public function pdfPath(): string
    {
        return $this->pdfPath;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->preventionPlanId();
    }

    public function payload(): array
    {
        return [
            'preventionPlanId' => (string) $this->preventionPlanId,
            'versionNumber' => $this->versionNumber,
            'pdfPath' => $this->pdfPath,
            'locale' => $this->locale->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $locale = Locale::tryFrom($payload['locale']);
        assert(null !== $locale);

        return new PreventionPlanVersionPDFSaved(
            preventionPlanId: new PreventionPlanId($payload['preventionPlanId']),
            versionNumber: (int) $payload['versionNumber'],
            pdfPath: $payload['pdfPath'],
            locale: $locale,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
