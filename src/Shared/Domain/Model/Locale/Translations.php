<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Locale;

class Translations implements \Countable
{
    private ?Locale $locale = null;

    /**
     * @param Translation[] $translations
     */
    public function __construct(private array $translations = []) {}

    public function setLocale(Locale $locale): void
    {
        $this->locale = $locale;
    }

    public function currentTranslation(): ?Translation
    {
        if (!$this->locale instanceof Locale) {
            return null;
        }

        return $this->get($this->locale);
    }

    public function add(Translation $translation): void
    {
        if (!$this->contains($translation)) {
            $this->translations[] = $translation;

            return ;
        }

        $this->get($translation->locale())->update($translation);
    }

    public function get(Locale $locale): ?Translation
    {
        if (0 === count($this->translations)) {
            return null;
        }

        foreach ($this->translations as $translation) {
            if ($locale === $translation->locale()) {
                return $translation;
            }
        }

        return null;
    }

    public function contains(Translation $translation): bool
    {
        return $this->get($translation->locale()) instanceof Translation;
    }

    /**
     * @return Translation[]
     */
    public function toArray(): array
    {
        return $this->translations;
    }

    public function count(): int
    {
        return count($this->translations);
    }
}
