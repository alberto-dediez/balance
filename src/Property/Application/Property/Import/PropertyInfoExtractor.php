<?php

declare(strict_types=1);

namespace App\Property\Application\Property\Import;

use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Domain\Model\Brand\BrandRepository;
use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Domain\Model\Category\CategoryRepository;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Domain\Model\Property\PropertyType;

class PropertyInfoExtractor
{
    public function __construct(
        private readonly DestinationRepository $destinationRepository,
        private readonly CountryRepository $countryRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly BrandRepository $brandRepository,
    ) {}

    public function extract(FieldExtractor $fieldExtractor, ExternalSource $source, array $row): PropertyInfo
    {
        $longitude = str_replace(',', '.', $fieldExtractor->extract(Field::InfoLongitude, $row));
        $latitude = str_replace(',', '.', $fieldExtractor->extract(Field::InfoLatitude, $row));

        $countryExternalId = $fieldExtractor->extract(Field::InfoCountryId, $row);
        $countryExternalName = $fieldExtractor->extract(Field::InfoCountryName, $row);

        $countryId = $this->getCountryId(
            $countryExternalId ?? md5((string) $countryExternalName),
            $countryExternalName,
            $source,
        );

        $destinationExternalId = $fieldExtractor->extract(Field::InfoDestinationId, $row);
        $destinationExternalName = $fieldExtractor->extract(Field::InfoDestinationName, $row);

        $destinationId = $this->getDestinationId(
            $destinationExternalId ?? md5((string) $destinationExternalName),
            $destinationExternalName,
            $source,
        );

        $brandExternalId = $fieldExtractor->extract(Field::InfoBrandId, $row);
        $brandExternalName = $fieldExtractor->extract(Field::InfoBrandName, $row);

        $brandId = $this->getBrandId(
            $brandExternalId ?? md5((string) $brandExternalName),
            $brandExternalName,
            $source,
        );

        $categoryExternalId = $fieldExtractor->extract(Field::InfoCategoryId, $row);
        $categoryExternalName = $fieldExtractor->extract(Field::InfoCategoryName, $row);

        $categoryId = $this->getCategoryId(
            $categoryExternalId ?? md5((string) $categoryExternalName),
            $categoryExternalName,
            $source,
        );

        $propertyTypeData = $fieldExtractor->extract(Field::InfoPropertyType, $row);

        return new PropertyInfo(
            propertyType: null == $propertyTypeData ? PropertyType::Other : PropertyType::translate($propertyTypeData),
            categoryId: $categoryId,
            brandId: $brandId,
            address: $fieldExtractor->extract(Field::InfoAddress, $row),
            postalCode: substr((string) $fieldExtractor->extract(Field::InfoPostalCode, $row), 0, 20),
            longitude: empty($longitude) ? null : $longitude,
            latitude: empty($latitude) ? null : $latitude,
            rooms: (int) $fieldExtractor->extract(Field::InfoRooms, $row),
            zone: $fieldExtractor->extract(Field::InfoZone, $row),
            web: $fieldExtractor->extract(Field::InfoWeb, $row),
            destinationId: $destinationId,
            countryId: $countryId,
        );
    }

    private function getBrandId(?string $brandId, ?string $brandName, ExternalSource $source): ?BrandId
    {
        if (null === $brandId && null === $brandName) {
            return null;
        }

        if (null === $brandId) {
            $brandId = md5($brandName);
        }

        $brand = $this->brandRepository->findBySourceAndExternalId($source, ExternalId::fromSourceAndId($source, $brandId));

        return $brand?->id();
    }

    private function getCategoryId(?string $categoryId, ?string $categoryName, ExternalSource $source): ?CategoryId
    {
        if (null === $categoryId && null === $categoryName) {
            return null;
        }

        if (null === $categoryId) {
            $categoryId = md5($categoryName);
        }

        $category = $this->categoryRepository->findBySourceAndExternalId($source, ExternalId::fromSourceAndId($source, $categoryId));

        return $category?->id();
    }

    private function getCountryId(?string $countryId, ?string $countryName, ExternalSource $source): ?CountryId
    {
        if (null === $countryId && null === $countryName) {
            return null;
        }

        if (null === $countryId) {
            $countryId = md5($countryName);
        }

        $country = $this->countryRepository->findBySourceAndExternalId($source, ExternalId::fromSourceAndId($source, $countryId));

        return $country?->id();
    }

    private function getDestinationId(?string $destinationId, ?string $destinationName, ExternalSource $source): ?DestinationId
    {
        if (null === $destinationId && null === $destinationName) {
            return null;
        }

        if (null === $destinationId) {
            $destinationId = md5($destinationName);
        }

        $destination = $this->destinationRepository->findBySourceAndExternalId($source, ExternalId::fromSourceAndId($source, $destinationId));

        return $destination?->id();
    }
}
