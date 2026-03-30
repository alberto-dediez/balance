<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Property;

class ExternalId implements \Stringable
{
    public function __construct(
        private readonly ?string $hotelbeds = null,
        private readonly ?string $restel = null,
        private readonly ?string $bioscore = null,
    ) {}

    public static function fromSourceAndId(ExternalSource $source, string $id): self
    {
        $hotelbeds = null;
        $restel = null;
        $bioscore = null;

        $attribute = $source->attribute();
        ${$attribute} = $id;

        return new self(
            hotelbeds: $hotelbeds,
            restel: $restel,
            bioscore: $bioscore,
        );
    }

    public function __toString(): string
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
        if (null !== $other->bioscore() && null !== $this->bioscore() && $other->bioscore() !== $this->bioscore()) {
            return false;
        }

        if (null !== $other->restel() && null !== $this->restel() && $other->restel() !== $this->restel()) {
            return false;
        }

        if (null !== $other->hotelbeds() && null !== $this->hotelbeds() && $other->hotelbeds() !== $this->hotelbeds()) {
            return false;
        }

        return true;
    }

    public function combine(ExternalId $other): ExternalId
    {
        return new ExternalId(
            hotelbeds: (null !== $this->hotelbeds() && !empty($this->hotelbeds())) ? $this->hotelbeds() : $other->hotelbeds(),
            restel: (null !== $this->restel() && !empty($this->restel())) ? $this->restel() : $other->restel(),
            bioscore: (null !== $this->bioscore() && !empty($this->bioscore())) ? $this->bioscore() : $other->bioscore(),
        );
    }
}
