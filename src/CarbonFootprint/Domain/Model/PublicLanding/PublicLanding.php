<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PublicLanding;

use App\Library\Domain\Model\Media\MediaId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class PublicLanding implements Translatable, \Stringable
{
    use AggregateRoot;

    /** @var iterable<PublicLandingTranslation>|Translations */
    private iterable $translations;

    /** @var Collection<int, PublicLandingBlock> */
    private Collection $blocks;

    /** @param array<DomainEvent> $events */
    public function __construct(
        private PublicLandingId $id,
        private ?PublicLandingOwnerType $ownerType = null,
        private string $ownerId = '',
        private ?AccountId $accountId = null,
        private bool $active = false,
        private string $title = '',
        private string $subtitle = '',
        private string $intro = '',
        private ?MediaId $heroImage = null,
        private string $ctaLabel = '',
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->translations = new Translations();
        $this->blocks = new ArrayCollection();
        $this->events = $events;
    }

    public function id(): PublicLandingId
    {
        return $this->id;
    }

    public function ownerType(): ?PublicLandingOwnerType
    {
        return $this->ownerType;
    }

    public function ownerId(): string
    {
        return $this->ownerId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function title(): string
    {
        /** @var PublicLandingTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->title() : $this->title;
    }

    public function subtitle(): string
    {
        /** @var PublicLandingTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->subtitle() : $this->subtitle;
    }

    public function intro(): string
    {
        /** @var PublicLandingTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->intro() : $this->intro;
    }

    public function ctaLabel(): string
    {
        /** @var PublicLandingTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->ctaLabel() : $this->ctaLabel;
    }

    public function heroImage(): ?MediaId
    {
        return $this->heroImage;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function translations(): Translations
    {
        if ($this->translations instanceof Translations) {
            return $this->translations;
        }

        $this->translations = new Translations(iterator_to_array($this->translations));

        return $this->translations;
    }

    /** @return list<PublicLandingBlock> */
    public function blocks(): array
    {
        $blocks = $this->blocks->toArray();

        usort(
            $blocks,
            static fn(PublicLandingBlock $left, PublicLandingBlock $right): int => $left->position() <=> $right->position(),
        );

        return $blocks;
    }

    public function hasContent(): bool
    {
        return '' !== trim($this->title) || '' !== trim($this->intro) || [] !== $this->blocks();
    }

    public function register(
        PublicLandingOwnerType $ownerType,
        string $ownerId,
        AccountId $accountId,
    ): void {
        $this->recordApplyAndPublish(
            new PublicLandingRegistered(
                publicLandingId: $this->id(),
                ownerType: $ownerType,
                ownerId: $ownerId,
                accountId: $accountId,
            ),
        );
    }

    public function update(
        bool $active,
        string $title,
        string $subtitle,
        string $intro,
        ?MediaId $heroImage,
        string $ctaLabel,
        ?Locale $locale = null,
    ): void {
        $translating = $locale instanceof Locale;

        $this->recordApplyAndPublish(
            new PublicLandingUpdated(
                publicLandingId: $this->id(),
                active: $active,
                title: $translating ? $this->title : $title,
                subtitle: $translating ? $this->subtitle : $subtitle,
                intro: $translating ? $this->intro : $intro,
                heroImage: $heroImage,
                ctaLabel: $translating ? $this->ctaLabel : $ctaLabel,
            ),
        );

        if ($locale instanceof Locale) {
            $this->translate($title, $subtitle, $intro, $ctaLabel, $locale);
        }
    }

    public function translate(
        string $title,
        string $subtitle,
        string $intro,
        string $ctaLabel,
        Locale $locale,
    ): void {
        $this->recordApplyAndPublish(
            new PublicLandingTranslated(
                publicLandingId: $this->id(),
                title: $title,
                subtitle: $subtitle,
                intro: $intro,
                ctaLabel: $ctaLabel,
                locale: $locale,
            ),
        );
    }

    public function setBlock(
        int $position,
        ?MediaId $image,
        string $title,
        string $text,
        ?Locale $locale = null,
    ): void {
        $current = $this->blockAt($position);

        if (null === $current) {
            $this->recordApplyAndPublish(
                new PublicLandingBlockAdded(
                    publicLandingId: $this->id(),
                    blockId: PublicLandingBlockId::generate(),
                    position: $position,
                    image: $image,
                    title: $title,
                    text: $text,
                ),
            );
        } else {
            $translating = $locale instanceof Locale;

            $this->recordApplyAndPublish(
                new PublicLandingBlockUpdated(
                    publicLandingId: $this->id(),
                    blockId: $current->id(),
                    position: $position,
                    image: $image,
                    title: $translating ? $current->defaultTitle() : $title,
                    text: $translating ? $current->defaultText() : $text,
                ),
            );
        }

        if (!$locale instanceof Locale) {
            return;
        }

        $block = $this->blockAt($position);

        if (null === $block) {
            return;
        }

        $this->recordApplyAndPublish(
            new PublicLandingBlockTranslated(
                publicLandingId: $this->id(),
                blockId: $block->id(),
                title: $title,
                text: $text,
                locale: $locale,
            ),
        );
    }

    public function truncateBlocks(int $count): void
    {
        foreach ($this->blocks() as $block) {
            if ($block->position() < $count) {
                continue;
            }

            $this->recordApplyAndPublish(
                new PublicLandingBlockRemoved(
                    publicLandingId: $this->id(),
                    blockId: $block->id(),
                ),
            );
        }
    }

    public function applyPublicLandingRegistered(PublicLandingRegistered $event): void
    {
        $this->ownerType = $event->ownerType();
        $this->ownerId = $event->ownerId();
        $this->accountId = $event->accountId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyPublicLandingUpdated(PublicLandingUpdated $event): void
    {
        $this->active = $event->isActive();
        $this->title = $event->title();
        $this->subtitle = $event->subtitle();
        $this->intro = $event->intro();
        $this->heroImage = $event->heroImage();
        $this->ctaLabel = $event->ctaLabel();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyPublicLandingTranslated(PublicLandingTranslated $event): void
    {
        $this->translations()->add(
            new PublicLandingTranslation(
                title: $event->title(),
                subtitle: $event->subtitle(),
                intro: $event->intro(),
                ctaLabel: $event->ctaLabel(),
                locale: $event->locale(),
                translatable: $this,
            ),
        );
    }

    public function applyPublicLandingBlockAdded(PublicLandingBlockAdded $event): void
    {
        $this->blocks->add(
            new PublicLandingBlock(
                id: $event->blockId(),
                publicLanding: $this,
                position: $event->position(),
                image: $event->image(),
                title: $event->title(),
                text: $event->text(),
            ),
        );

        $this->updatedAt = $event->occurredOn();
    }

    public function applyPublicLandingBlockUpdated(PublicLandingBlockUpdated $event): void
    {
        $this->block($event->blockId())?->update(
            position: $event->position(),
            image: $event->image(),
            title: $event->title(),
            text: $event->text(),
        );

        $this->updatedAt = $event->occurredOn();
    }

    public function applyPublicLandingBlockTranslated(PublicLandingBlockTranslated $event): void
    {
        $this->block($event->blockId())?->translate(
            title: $event->title(),
            text: $event->text(),
            locale: $event->locale(),
        );
    }

    public function applyPublicLandingBlockRemoved(PublicLandingBlockRemoved $event): void
    {
        $block = $this->block($event->blockId());

        if (null !== $block) {
            $this->blocks->removeElement($block);
        }

        $this->updatedAt = $event->occurredOn();
    }

    private function blockAt(int $position): ?PublicLandingBlock
    {
        foreach ($this->blocks as $block) {
            if ($block->position() === $position) {
                return $block;
            }
        }

        return null;
    }

    private function block(PublicLandingBlockId $blockId): ?PublicLandingBlock
    {
        foreach ($this->blocks as $block) {
            if ($block->id()->equals($blockId)) {
                return $block;
            }
        }

        return null;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
