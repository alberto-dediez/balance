<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\Locale;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;

class Translations extends ArrayCollection implements \Countable
{
    private ?Locale $locale = null;

    public function __construct(array|PersistentCollection $translations = [])
    {
        parent::__construct(is_array($translations) ? $translations : $translations->toArray());
    }

    public function add(mixed $element): void
    {
        if (!$element instanceof Translation) {
            throw new \InvalidArgumentException('$element must be an instance of Translation');
        }

        if (!$this->contains($element)) {
            parent::add($element);
        }

        $this->getTranslationFor($element->locale())->update($element);
    }

    public function removeElement(mixed $element): void
    {
        if (!$element instanceof Translation) {
            throw new \InvalidArgumentException('$element must be an instance of Translation');
        }

        if (count($this) == 0) {
            return ;
        }

        foreach ($this->toArray() as $i => $savedTranslation) {
            if ($element->locale() === $savedTranslation->locale()) {
                parent::removeElement($savedTranslation);
            }
        }
    }

    public function contains(mixed $element): bool
    {
        if (!$element instanceof Translation) {
            throw new \InvalidArgumentException('$element must be an instance of Translation');
        }

        if (count($this) == 0) {
            return false;
        }

        foreach ($this as $savedTranslation) {
            if ($element->locale() === $savedTranslation->locale()) {
                return true;
            }
        }

        return false;
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

        return $this->getTranslationFor($this->locale);
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
