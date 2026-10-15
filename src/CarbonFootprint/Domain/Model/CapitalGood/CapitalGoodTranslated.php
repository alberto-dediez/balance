<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CapitalGood;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class CapitalGoodTranslated extends DomainEvent
{
    protected static string $eventName = 'capitalGoodTranslated';

    public function __construct(
        private CapitalGoodId $capitalGoodId,
        private string $name,
        private Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function capitalGoodId(): CapitalGoodId
    {
        return $this->capitalGoodId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->capitalGoodId();
    }

    public function payload(): array
    {
        return [
            'capitalGoodId' => (string) $this->capitalGoodId,
            'name' => $this->name,
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

        return new CapitalGoodTranslated(
            capitalGoodId: new CapitalGoodId($payload['capitalGoodId']),
            name: $payload['name'],
            locale: $locale,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
