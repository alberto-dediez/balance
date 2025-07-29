<?php

namespace App\Property\Domain\Model\Property;

class ExternalId
{
    public function __construct(
        private ?string $hotelbeds = null,
        private ?string $restel = null,
        private ?string $bioscore = null
    ) {

    }

    public static function fromSourceAndId(ExternalSource $source, string $id): self
    {
        $hotelbeds = null;
        $restel = null;
        $bioscore = null;

        $attribute = $source->attribute();
        $$attribute = $id;

        return new self(
            hotelbeds: $hotelbeds,
            restel: $restel,
            bioscore: $bioscore,
        );
    }

    public function __toString()
    {
        return implode(' | ', [
            'hotelbeds => ' . $this->hotelbeds(),
            'restel => ' . $this->restel(),
            'bioscore => ' . $this->bioscore(),
        ]);
    }

    public function hotelbeds(): ?string
    {
        return $this->hotelbeds;
    }

    public function restel(): ?string
    {
        return $this->restel;
    }

    public function bioscore(): ?string
    {
        return $this->bioscore;
    }

    public function equals(ExternalId $externalId): bool
    {
        return $this->hotelbeds() === $externalId->hotelbeds()
            && $this->restel() === $externalId->restel()
            && $this->bioscore() === $externalId->bioscore()
        ;
    }

    public function isEmpty(): bool
    {
        return null == $this->hotelbeds
            && null == $this->restel
            && null == $this->bioscore
        ;
    }

    public function isCombinable(ExternalId $other): bool
    {
        if ($other->bioscore() !== null && $this->bioscore() !== null && $other->bioscore() != $this->bioscore()) {
            return false;
        }

        if ($other->restel() !== null && $this->restel() !== null && $other->restel() != $this->restel()) {
            return false;
        }

        if ($other->hotelbeds() !== null && $this->hotelbeds() !== null && $other->hotelbeds() != $this->hotelbeds()) {
            return false;
        }

        return true;
    }

    public function combine(ExternalId $other): ExternalId
    {
        return new ExternalId(
            bioscore: (null !== $this->bioscore() && !empty($this->bioscore())) ? $this->bioscore() : $other->bioscore(),
            restel: (null !== $this->restel() && !empty($this->restel())) ? $this->restel() : $other->restel(),
            hotelbeds: (null !== $this->hotelbeds() && !empty($this->hotelbeds())) ? $this->hotelbeds() : $other->hotelbeds(),
        );
    }
}