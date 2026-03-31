<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\ServiceCategory;

use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;

class ServiceCategory implements Translatable
{
    use AggregateRoot;

    /** @var iterable<ServiceCategoryTranslation>|Translations */
    private iterable $translations;

    /** @param array<mixed> $events */
    public function __construct(
        private ServiceCategoryId $id,
        private string $name = '',
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();

        $this->events = $events;
    }

    public function id(): ServiceCategoryId
    {
        return $this->id;
    }

    public function name(): string
    {
        /** @var ServiceCategoryTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->name() : $this->name;
    }

    public function translations(): Translations
    {
        if ($this->translations instanceof Translations) {
            return $this->translations;
        }

        $this->translations = new Translations(iterator_to_array($this->translations));

        return $this->translations;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function register(
        string $name,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new ServiceCategoryRegistered(
                serviceCategoryId: $this->id(),
                name: $name,
            ),
        );

        if (null !== $locale) {
            $this->translate($name, $locale);
        }
    }

    public function update(
        string $name,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new ServiceCategoryUpdated(
                serviceCategoryId: $this->id(),
                name: null === $locale ? $name : $this->name,
            ),
        );

        if (null !== $locale) {
            $this->translate($name, $locale);
        }
    }

    public function translate(string $name, Locale $locale): void
    {
        $this->recordApplyAndPublish(
            new ServiceCategoryTranslated(
                serviceCategoryId: $this->id(),
                name: $name,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new ServiceCategoryRemoved(
                serviceCategoryId: $this->id(),
            ),
        );
    }

    public function applyServiceCategoryRegistered(ServiceCategoryRegistered $event): void
    {
        $this->name = $event->name();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyServiceCategoryUpdated(ServiceCategoryUpdated $event): void
    {
        $this->name = $event->name();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyServiceCategoryTranslated(ServiceCategoryTranslated $event): void
    {
        $serviceCategoryTranslation = new ServiceCategoryTranslation(
            name: $event->name(),
            locale: $event->locale(),
            translatable: $this,
        );

        $this->translations()->add($serviceCategoryTranslation);
    }

    public function applyServiceCategoryRemoved(ServiceCategoryRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->name();
    }
}
