<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class CarbonFootprintReportTranslated extends DomainEvent
{
    protected static string $eventName = 'carbonFootprintReportTranslated';

    public function __construct(
        private CarbonFootprintReportId $carbonFootprintReportId,
        private string $filePath,
        private Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function carbonFootprintReportId(): CarbonFootprintReportId
    {
        return $this->carbonFootprintReportId;
    }

    public function filePath(): string
    {
        return $this->filePath;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->carbonFootprintReportId;
    }

    public function payload(): array
    {
        return [
            'carbonFootprintReportId' => (string) $this->carbonFootprintReportId,
            'filePath' => $this->filePath,
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

        return new self(
            carbonFootprintReportId: new CarbonFootprintReportId($payload['carbonFootprintReportId']),
            filePath: $payload['filePath'],
            locale: $locale,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
