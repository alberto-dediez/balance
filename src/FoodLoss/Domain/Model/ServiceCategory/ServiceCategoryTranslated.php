<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\ServiceCategory;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class ServiceCategoryTranslated extends DomainEvent
{
    protected static string $eventName = 'serviceCategoryTranslated';

    public function __construct(
        private readonly ServiceCategoryId $serviceCategoryId,
        private readonly string $name,
        private readonly Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function serviceCategoryId(): ServiceCategoryId
    {
        return $this->serviceCategoryId;
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
        return $this->serviceCategoryId;
    }

    public function payload(): array
    {
        return [
            'serviceCategoryId' => (string) $this->serviceCategoryId,
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

        return new ServiceCategoryTranslated(
            serviceCategoryId: new ServiceCategoryId($payload['serviceCategoryId']),
            name: $payload['name'],
            locale: $locale,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
