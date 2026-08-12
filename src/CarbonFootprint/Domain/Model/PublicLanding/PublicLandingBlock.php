<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PublicLanding;

use App\Library\Domain\Model\Media\MediaId;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translatable;
use App\Shared\Domain\Model\Locale\Translations;

class PublicLandingBlock implements Translatable
{
    /** @var iterable<PublicLandingBlockTranslation>|Translations */
    private iterable $translations;

    public function __construct(
        private PublicLandingBlockId $id,
        private PublicLanding $publicLanding,
        private int $position = 0,
        private ?MediaId $image = null,
        private string $title = '',
        private string $text = '',
    ) {
        $this->translations = new Translations();
    }

    public function id(): PublicLandingBlockId
    {
        return $this->id;
    }

    public function publicLanding(): PublicLanding
    {
        return $this->publicLanding;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function image(): ?MediaId
    {
        return $this->image;
    }

    public function title(): string
    {
        /** @var PublicLandingBlockTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->title() : $this->title;
    }

    public function text(): string
    {
        /** @var PublicLandingBlockTranslation|null $translation */
        $translation = $this->translations()->currentTranslation();

        return null !== $translation ? $translation->text() : $this->text;
    }

    public function defaultTitle(): string
    {
        return $this->title;
    }

    public function defaultText(): string
    {
        return $this->text;
    }

    public function translations(): Translations
    {
        if ($this->translations instanceof Translations) {
            return $this->translations;
        }

        $this->translations = new Translations(iterator_to_array($this->translations));

        return $this->translations;
    }

    public function update(int $position, ?MediaId $image, string $title, string $text): void
    {
        $this->position = $position;
        $this->image = $image;
        $this->title = $title;
        $this->text = $text;
    }

    public function translate(string $title, string $text, Locale $locale): void
    {
        $this->translations()->add(
            new PublicLandingBlockTranslation(
                title: $title,
                text: $text,
                locale: $locale,
                translatable: $this,
            ),
        );
    }

    public function isEmpty(): bool
    {
        return null === $this->image && '' === trim($this->title) && '' === trim($this->text);
    }
}
