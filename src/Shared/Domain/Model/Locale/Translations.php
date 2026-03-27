<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Locale;

use Doctrine\Common\Collections\ArrayCollection;

/** @extends ArrayCollection<int, Translation> */
class Translations extends ArrayCollection implements \Countable
{
    public function add(mixed $element): void
    {
        if (!$element instanceof Translation) {
            throw new \InvalidArgumentException('$element must be an instance of Translation');
        }

        if (!$this->contains($element)) {
            parent::add($element);
        }

        $this->getTranslationFor($element->locale())?->update($element);
    }

    public function removeElement(mixed $element): bool
    {
        if (0 == count($this)) {
            return false;
        }

        $removed = false;
        foreach ($this->toArray() as $savedTranslation) {
            if ($element->locale() === $savedTranslation->locale()) {
                $removed = parent::removeElement($savedTranslation);
            }
        }

        return $removed;
    }

    public function contains(mixed $element): bool
    {
        if (!$element instanceof Translation) {
            throw new \InvalidArgumentException('$element must be an instance of Translation');
        }

        if (0 == count($this)) {
            return false;
        }

        foreach ($this as $savedTranslation) {
            if ($element->locale() === $savedTranslation->locale()) {
                return true;
            }
        }

        return false;
    }

    public function currentTranslation(): ?Translation
    {
        return $this->getTranslationFor(LocaleGetter::locale());
    }

    public function getTranslationFor(Locale $locale): ?Translation
    {
        $translations = $this->toArray();

        if (0 === count($translations)) {
            return null;
        }

        foreach ($translations as $translation) {
            if ($locale === $translation->locale()) {
                return $translation;
            }
        }

        return null;
    }
}
