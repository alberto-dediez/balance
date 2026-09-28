<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityTopic;

use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;

class MaterialityTopic implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<MaterialityTopicTranslation>|Translations */
    private iterable $translations;

    /** @param array<mixed> $events */
    public function __construct(
        private MaterialityTopicId $id,
        private ?int $number = null,
        private MaterialityScope $scope = MaterialityScope::Scope3,
        private ?MaterialityTopicDirection $direction = null,
        private string $name = '',
        private string $description = '',
        private int $position = 0,
        private bool $active = true,
        private bool $standard = false,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->translations = new Translations();
        $this->events = $events;
    }

    public function id(): MaterialityTopicId
    {
        return $this->id;
    }

    public function number(): ?int
    {
        return $this->number;
    }

    public function scope(): MaterialityScope
    {
        return $this->scope;
    }

    public function direction(): ?MaterialityTopicDirection
    {
        return $this->direction;
    }

    public function name(): string
    {
        /** @var MaterialityTopicTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->name() : $this->name;
    }

    public function description(): string
    {
        /** @var MaterialityTopicTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->description() : $this->description;
    }

    public function defaultName(): string
    {
        return $this->name;
    }

    public function defaultDescription(): string
    {
        return $this->description;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isStandard(): bool
    {
        return $this->standard;
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

    public function translations(): Translations
    {
        if ($this->translations instanceof Translations) {
            return $this->translations;
        }

        $this->translations = new Translations(iterator_to_array($this->translations));

        return $this->translations;
    }

    public function register(
        ?int $number,
        MaterialityScope $scope,
        ?MaterialityTopicDirection $direction,
        string $name,
        string $description,
        int $position,
        bool $standard,
        ?Locale $locale = null,
    ): void {
        if ($standard && null === $number) {
            throw new StandardMaterialityTopicNumberRequiredException();
        }

        $this->recordApplyAndPublish(
            new MaterialityTopicRegistered(
                materialityTopicId: $this->id(),
                number: $number,
                scope: $scope,
                direction: $this->resolveDirection($scope, $direction),
                name: $name,
                description: $description,
                position: $position,
                standard: $standard,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $description, $locale);
        }
    }

    public function update(
        MaterialityScope $scope,
        ?MaterialityTopicDirection $direction,
        string $name,
        string $description,
        int $position,
        bool $active,
        ?Locale $locale = null,
    ): void {
        $this->recordApplyAndPublish(
            new MaterialityTopicUpdated(
                materialityTopicId: $this->id(),
                scope: $scope,
                direction: $this->resolveDirection($scope, $direction),
                name: $locale instanceof Locale ? $this->name : $name,
                description: $locale instanceof Locale ? $this->description : $description,
                position: $position,
                active: $active,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($name, $description, $locale);
        }
    }

    public function translate(string $name, string $description, Locale $locale): void
    {
        $this->recordApplyAndPublish(
            new MaterialityTopicTranslated(
                materialityTopicId: $this->id(),
                name: $name,
                description: $description,
                locale: $locale,
            ),
        );
    }

    public function remove(): void
    {
        if ($this->standard) {
            throw new StandardMaterialityTopicCannotBeRemovedException($this->id());
        }

        if ($this->removed) {
            return;
        }

        $this->recordApplyAndPublish(
            new MaterialityTopicRemoved(
                materialityTopicId: $this->id(),
            ),
        );
    }

    private function resolveDirection(MaterialityScope $scope, ?MaterialityTopicDirection $direction): ?MaterialityTopicDirection
    {
        if (MaterialityScope::Scope3 !== $scope) {
            return null;
        }

        if (null === $direction) {
            throw new MaterialityTopicDirectionRequiredException();
        }

        return $direction;
    }

    public function applyMaterialityTopicRegistered(MaterialityTopicRegistered $event): void
    {
        $this->number = $event->number();
        $this->scope = $event->scope();
        $this->direction = $event->direction();
        $this->name = $event->name();
        $this->description = $event->description();
        $this->position = $event->position();
        $this->standard = $event->standard();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyMaterialityTopicUpdated(MaterialityTopicUpdated $event): void
    {
        $this->scope = $event->scope();
        $this->direction = $event->direction();
        $this->name = $event->name();
        $this->description = $event->description();
        $this->position = $event->position();
        $this->active = $event->active();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyMaterialityTopicTranslated(MaterialityTopicTranslated $event): void
    {
        $this->translations()->add(
            new MaterialityTopicTranslation(
                name: $event->name(),
                description: $event->description(),
                locale: $event->locale(),
                translatable: $this,
            ),
        );
    }

    public function applyMaterialityTopicRemoved(MaterialityTopicRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
