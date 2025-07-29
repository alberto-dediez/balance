<?php

namespace App\Property\Domain\Model\Property;

use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Domain\Model\Property\PropertyType;
use App\Property\Domain\Model\Destination\DestinationId;

class PropertyInfo
{
    public function __construct(
        private string|PropertyType $propertyType = PropertyType::Hotel,
        private string|CategoryId|null $categoryId = null,
        private string|BrandId|null $brandId = null,
        private ?string $address = null,
        private ?string $postalCode = null,
        private ?string $longitude = null,
        private ?string $latitude = null,
        private ?int $rooms = 0,
        private ?string $zone = null,
        private ?string $web = null,
        private DestinationId|string|null $destinationId = null,
        private CountryId|string|null $countryId = null
    ) {

    }

    public function __toString()
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
            'destinationId => ' . (string) $this->destinationId(),
            'countryId => ' . (string) $this->countryId(),
        ]);
    }

    public function propertyType(): PropertyType
    {
        if (is_string($this->propertyType)) {
            $this->propertyType = PropertyType::tryFrom($this->propertyType);
        }

        return $this->propertyType;
    }

    public function categoryId(): ?CategoryId
    {
        if (is_string($this->categoryId)) {
            $this->categoryId = new CategoryId($this->categoryId);
        }

        return $this->categoryId;
    }

    public function brandId(): ?BrandId
    {
        if (is_string($this->brandId)) {
            $this->brandId = new BrandId($this->brandId);
        }

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
        if (is_string($this->destinationId)) {
            $this->destinationId = new DestinationId($this->destinationId);
        }

        return $this->destinationId;
    }

    public function countryId(): ?CountryId
    {
        if (is_string($this->countryId)) {
            $this->countryId = new CountryId($this->countryId);
        }

        return $this->countryId;
    }

    public function equals(PropertyInfo $other): bool
    {
        if ($this->propertyType() !== $other->propertyType()) {
            return false;
        }

        if (
            (null == $this->categoryId() && null !== $other->categoryId())
            || (null !== $this->categoryId() && null == $other->categoryId())
            || (
                null !== $this->categoryId()
                && null !== $other->categoryId()
                && !$this->categoryId()->equals($other->categoryId())
            )
        ) {
            return false;
        }

        if (
            (null == $this->brandId() && null !== $other->brandId())
            || (null !== $this->brandId() && null == $other->brandId())
            || (
                null !== $this->brandId()
                && null !== $other->brandId()
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
            (null == $this->destinationId() && null !== $other->destinationId())
            || (null !== $this->destinationId() && null == $other->destinationId())
            || (
                null !== $this->destinationId()
                && null !== $other->destinationId()
                && !$this->destinationId()->equals($other->destinationId())
            )
        ) {
            return false;
        }

        if (
            (null == $this->countryId() && null !== $other->countryId())
            || (null !== $this->countryId() && null == $other->countryId())
            || (
                null !== $this->countryId()
                && null !== $other->countryId()
                && !$this->countryId()->equals($other->countryId())
            )
        ) {
            return false;
        }

        return true;
    } 

    public function combine(PropertyInfo $other): PropertyInfo
    {
        return new PropertyInfo(
            propertyType: null !== $this->propertyType() ? $this->propertyType() : $other->propertyType(),
            categoryId: (null !== $this->categoryId() && !empty($this->categoryId())) ? $this->categoryId() : $other->categoryId(),
            brandId: (null !== $this->brandId() && !empty($this->brandId())) ? $this->brandId() : $other->brandId(),
            address: (null !== $this->address() && !empty($this->address())) ? $this->address() : $other->address(),
            postalCode: (null !== $this->postalCode() && !empty($this->postalCode())) ? $this->postalCode() : $other->postalCode(),
            longitude: (null !== $this->longitude() && !empty($this->longitude())) ? $this->longitude() : $other->longitude(),
            latitude: (null !== $this->latitude() && !empty($this->latitude())) ? $this->latitude() : $other->latitude(),
            rooms: (null !== $this->rooms() && $this->rooms() > 0) ? $this->rooms() : $other->rooms(),
            zone: (null !== $this->zone() && !empty($this->zone())) ? $this->zone() : $other->zone(),
            web: (null !== $this->web() && !empty($this->web())) ? $this->web() : $other->web(),
            destinationId: null !== $this->destinationId() ? $this->destinationId() : $other->destinationId(),
            countryId: null !== $this->countryId() ? $this->countryId() : $other->countryId(),
        );
    }
}