<?php

namespace App\Property\Application\Property\Import;

use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Application\Property\Import\Field;
use App\Property\Domain\Model\Brand\BrandRepository;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Domain\Model\Property\PropertyType;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Category\CategoryRepository;
use App\Property\Domain\Model\Destination\DestinationRepository;

class PropertyInfoExtractor
{
    public function __construct(
        private DestinationRepository $destinationRepository, 
        private CountryRepository $countryRepository,
        private CategoryRepository $categoryRepository,
        private BrandRepository $brandRepository
    ) {

    }

    public function extract(FieldExtractor $fieldExtractor, ExternalSource $source, array $row): PropertyInfo
    {
        $longitude = str_replace(',', '.', $fieldExtractor->extract(Field::InfoLongitude, $row));
        $latitude = str_replace(',', '.', $fieldExtractor->extract(Field::InfoLatitude, $row));

        $countryExternalId = $fieldExtractor->extract(Field::InfoCountryId, $row);
        $countryExternalName = $fieldExtractor->extract(Field::InfoCountryName, $row);

        $countryId = $this->getCountryId(
            null !== $countryExternalId ? $countryExternalId : md5($countryExternalName),
            $countryExternalName,
            $source
        );

        $destinationExternalId = $fieldExtractor->extract(Field::InfoDestinationId, $row);
        $destinationExternalName = $fieldExtractor->extract(Field::InfoDestinationName, $row);

        $destinationId = $this->getDestinationId(
            null !== $destinationExternalId ? $destinationExternalId : md5($destinationExternalName),
            $destinationExternalName,
            $source
        );

        $brandExternalId = $fieldExtractor->extract(Field::InfoBrandId, $row);
        $brandExternalName = $fieldExtractor->extract(Field::InfoBrandName, $row);

        $brandId = $this->getBrandId(
            null !== $brandExternalId ? $brandExternalId : md5($brandExternalName),
            $brandExternalName,
            $source
        );

        $categoryExternalId = $fieldExtractor->extract(Field::InfoCategoryId, $row);
        $categoryExternalName = $fieldExtractor->extract(Field::InfoCategoryName, $row);

        $categoryId = $this->getCategoryId(
            null !== $categoryExternalId ? $categoryExternalId : md5($categoryExternalName),
            $categoryExternalName,
            $source
        );

        $propertyTypeData = $fieldExtractor->extract(Field::InfoPropertyType, $row);

        return new PropertyInfo(
            propertyType: null == $propertyTypeData ? PropertyType::Other : PropertyType::translate($propertyTypeData),
            categoryId: $categoryId,
            address: $fieldExtractor->extract(Field::InfoAddress, $row),
            postalCode: substr($fieldExtractor->extract(Field::InfoPostalCode, $row), 0, 20),
            longitude: !empty($longitude) ? $longitude : null,
            latitude: !empty($latitude) ? $latitude : null,
            rooms: (int) $fieldExtractor->extract(Field::InfoRooms, $row),
            web: $fieldExtractor->extract(Field::InfoWeb, $row),
            zone: $fieldExtractor->extract(Field::InfoZone, $row),
            destinationId: $destinationId,
            countryId: $countryId,
            brandId: $brandId,
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