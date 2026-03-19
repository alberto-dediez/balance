<?php

namespace App\Property\Domain\Model\Property;

use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Domain\Model\Property\PropertyType;
use App\Property\Domain\Model\Destination\DestinationId;

class PropertyInfo implements \Stringable
{
    public function __construct(
        private readonly PropertyType $propertyType = PropertyType::Hotel,
        private readonly CategoryId|null $categoryId = null,
        private readonly BrandId|null $brandId = null,
        private readonly ?string $address = null,
        private readonly ?string $postalCode = null,
        private readonly ?string $longitude = null,
        private readonly ?string $latitude = null,
        private readonly ?int $rooms = 0,
        private readonly ?string $zone = null,
        private readonly ?string $web = null,
        private readonly DestinationId|null $destinationId = null,
        private readonly CountryId|null $countryId = null
    ) {

    }

    public function __toString(): string
    {
        return implode(' | ', [
            'propertyType => ' . $this->propertyType()->value,
            'categoryId => ' . $this->categoryId(),
            'brandId => ' . $this->brandId(),
            'address => ' . $this->address(),
            'postalCode => ' . $this->postalCode(),
            'longitude => ' . $this->longitude(),
            'latitude => ' . $this->latitude(),
            'rooms => ' . $this->rooms(),
            'zone => ' . $this->zone(),
            'web => ' . $this->web(),
            'destinationId => ' . $this->destinationId(),
            'countryId => ' . $this->countryId(),
        ]);
    }

    public function propertyType(): PropertyType
    {
        return $this->propertyType;
    }

    public function categoryId(): ?CategoryId
    {
        return $this->categoryId;
    }

    public function brandId(): ?BrandId
    {
        return $this->brandId;
    }

    public function address(): ?string
    {
        return $this->address;
    }

    public function postalCode(): ?string
    {
        return $this->postalCode;
    }

    public function longitude(): ?string
    {
        return $this->longitude;
    }

    public function latitude(): ?string
    {
        return $this->latitude;
    }

    public function rooms(): ?int
    {
        return $this->rooms;
    }

    public function zone(): ?string
    {
        return $this->zone;
    }

    public function web(): ?string
    {
        return $this->web;
    }

    public function destinationId(): ?DestinationId
    {
        return $this->destinationId;
    }

    public function countryId(): ?CountryId
    {
        return $this->countryId;
    }

    public function equals(PropertyInfo $other): bool
    {
        if ($this->propertyType() !== $other->propertyType()) {
            return false;
        }

        if (
            (null == $this->categoryId() && $other->categoryId() instanceof CategoryId)
            || ($this->categoryId() instanceof CategoryId && null == $other->categoryId())
            || (
                $this->categoryId() instanceof CategoryId
                && $other->categoryId() instanceof CategoryId
                && !$this->categoryId()->equals($other->categoryId())
            )
        ) {
            return false;
        }

        if (
            (null == $this->brandId() && $other->brandId() instanceof BrandId)
            || ($this->brandId() instanceof BrandId && null == $other->brandId())
            || (
                $this->brandId() instanceof BrandId
                && $other->brandId() instanceof BrandId
                && !$this->brandId()->equals($other->brandId())
            )
        ) {
            return false;
        }

        if ($this->address() !== $other->address()) {
            return false;
        }

        if ($this->postalCode() !== $other->postalCode()) {
            return false;
        }

        if ($this->longitude() !== $other->longitude()) {
            return false;
        }

        if ($this->latitude() !== $other->latitude()) {
            return false;
        }

        if ($this->rooms() !== $other->rooms()) {
            return false;
        }

        if ($this->zone() !== $other->zone()) {
            return false;
        }

        if ($this->web() !== $other->web()) {
            return false;
        }

        if (
            (null == $this->destinationId() && $other->destinationId() instanceof DestinationId)
            || ($this->destinationId() instanceof DestinationId && null == $other->destinationId())
            || (
                $this->destinationId() instanceof DestinationId
                && $other->destinationId() instanceof DestinationId
                && !$this->destinationId()->equals($other->destinationId())
            )
        ) {
            return false;
        }
        return !(null == $this->countryId() && $other->countryId() instanceof CountryId || $this->countryId() instanceof CountryId && null == $other->countryId() || $this->countryId() instanceof CountryId && $other->countryId() instanceof CountryId && !$this->countryId()->equals($other->countryId()));
    } 

    public function combine(PropertyInfo $other): PropertyInfo
    {
        return new PropertyInfo(
            propertyType: $this->propertyType() ?? $other->propertyType(),
            categoryId: ($this->categoryId() instanceof CategoryId && !empty($this->categoryId())) ? $this->categoryId() : $other->categoryId(),
            brandId: ($this->brandId() instanceof BrandId && !empty($this->brandId())) ? $this->brandId() : $other->brandId(),
            address: (null !== $this->address() && !empty($this->address())) ? $this->address() : $other->address(),
            postalCode: (null !== $this->postalCode() && !empty($this->postalCode())) ? $this->postalCode() : $other->postalCode(),
            longitude: (null !== $this->longitude() && !empty($this->longitude())) ? $this->longitude() : $other->longitude(),
            latitude: (null !== $this->latitude() && !empty($this->latitude())) ? $this->latitude() : $other->latitude(),
            rooms: (null !== $this->rooms() && $this->rooms() > 0) ? $this->rooms() : $other->rooms(),
            zone: (null !== $this->zone() && !empty($this->zone())) ? $this->zone() : $other->zone(),
            web: (null !== $this->web() && !empty($this->web())) ? $this->web() : $other->web(),
            destinationId: $this->destinationId() ?? $other->destinationId(),
            countryId: $this->countryId() ?? $other->countryId(),
        );
    }
}