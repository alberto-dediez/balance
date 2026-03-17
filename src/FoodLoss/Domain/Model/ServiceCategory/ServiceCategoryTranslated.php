<?php

namespace App\FoodLoss\Domain\Model\ServiceCategory;

use DateTimeImmutable;
use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\AggregateRootId;

final class ServiceCategoryTranslated extends DomainEvent
{
    protected static string $eventName = 'serviceCategoryTranslated';

    public function __construct(
        private ServiceCategoryId $serviceCategoryId, 
        private string $name, 
        private Locale $locale,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
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

    public function aggregateRootId(): ?AggregateRootId
    {
        return $this->serviceCategoryId();
    }

    public function payload(): ?array
    {
        return [
            'serviceCategoryId' => (string) $this->serviceCategoryId,
            'name' => $this->name,
            'locale' => $this->locale->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new ServiceCategoryTranslated(
            serviceCategoryId: new ServiceCategoryId($payload['serviceCategoryId']), 
            name: $payload['name'],
            locale: Locale::tryFrom($payload['locale']),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}