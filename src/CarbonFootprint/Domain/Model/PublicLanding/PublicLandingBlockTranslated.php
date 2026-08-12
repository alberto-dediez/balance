<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PublicLanding;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class PublicLandingBlockTranslated extends DomainEvent
{
    protected static string $eventName = 'publicLandingBlockTranslated';

    public function __construct(
        private PublicLandingId $publicLandingId,
        private PublicLandingBlockId $blockId,
        private string $title,
        private string $text,
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

    public function blockId(): PublicLandingBlockId
    {
        return $this->blockId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function text(): string
    {
        return $this->text;
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
            'blockId' => (string) $this->blockId,
            'title' => $this->title,
            'text' => $this->text,
            'locale' => $this->locale->value,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        $locale = Locale::tryFrom($payload['locale']);
        assert(null !== $locale);

        return new self(
            publicLandingId: new PublicLandingId($payload['publicLandingId']),
            blockId: new PublicLandingBlockId($payload['blockId']),
            title: $payload['title'],
            text: $payload['text'],
            locale: $locale,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
