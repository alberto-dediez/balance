<?php

namespace App\Shared\Domain\Model\Locale;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class Translations implements \Countable
{
    private ?Locale $locale = null;

    /**
     * @param list<Translation> $translations
     */
    public function __construct(private array $translations = [])
    {

    }

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
        if ($this->contains($translation)) {
            $this->remove($translation);
        }

        $this->translations[] = $translation;
    }

    public function remove(Translation $translation): void
    {
        if (count($this->translations) === 0) {
            return ;
        }

        foreach ($this->translations as $i => $savedTranslation) {
            if ($translation->locale() === $savedTranslation->locale()) {
                unset($this->translations[$i]);
            }
        }
    }

    public function get(Locale $locale): ?Translation
    {
        if (count($this->translations) === 0) {
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
