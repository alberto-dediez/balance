<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PublicLanding;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class PublicLandingTranslated extends DomainEvent
{
    protected static string $eventName = 'publicLandingTranslated';

    public function __construct(
        private PublicLandingId $publicLandingId,
        private string $title,
        private string $subtitle,
        private string $intro,
        private string $ctaLabel,
        private Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function publicLandingId(): PublicLandingId
    {
        return $this->publicLandingId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function subtitle(): string
    {
        return $this->subtitle;
    }

    public function intro(): string
    {
        return $this->intro;
    }

    public function ctaLabel(): string
    {
        return $this->ctaLabel;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->publicLandingId();
    }

    public function payload(): array
    {
        return [
            'publicLandingId' => (string) $this->publicLandingId,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'intro' => $this->intro,
            'ctaLabel' => $this->ctaLabel,
            'locale' => $this->locale->value,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        $locale = Locale::tryFrom($payload['locale']);
        assert(null !== $locale);

        return new self(
            publicLandingId: new PublicLandingId($payload['publicLandingId']),
            title: $payload['title'],
            subtitle: $payload['subtitle'],
            intro: $payload['intro'],
            ctaLabel: $payload['ctaLabel'],
            locale: $locale,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
