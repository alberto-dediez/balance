<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityQuestion;

use App\Materiality\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class MaterialityQuestionTranslated extends DomainEvent
{
    protected static string $eventName = 'materialityQuestionTranslated';

    public function __construct(
        private MaterialityQuestionId $materialityQuestionId,
        private string $text,
        private string $help,
        private Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function materialityQuestionId(): MaterialityQuestionId
    {
        return $this->materialityQuestionId;
    }

    public function text(): string
    {
        return $this->text;
    }

    public function help(): string
    {
        return $this->help;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->materialityQuestionId();
    }

    public function payload(): array
    {
        return [
            'materialityQuestionId' => (string) $this->materialityQuestionId,
            'text' => $this->text,
            'help' => $this->help,
            'locale' => $this->locale->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MaterialityQuestionTranslated(
            materialityQuestionId: new MaterialityQuestionId($payload['materialityQuestionId']),
            text: $payload['text'],
            help: $payload['help'],
            locale: Locale::from($payload['locale']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
