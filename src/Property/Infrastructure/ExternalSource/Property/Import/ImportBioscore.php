<?php

namespace App\Property\Infrastructure\ExternalSource\Property\Import;

use App\Property\Application\Property\Import\Field;
use App\Property\Domain\Model\Property\ExternalSource;
use App\Property\Application\Property\Import\FieldExtractor;

class ImportBioscore implements ImportConfiguration
{
    public function getFieldExtractor(): FieldExtractor
    {
        return new FieldExtractor([
            Field::ExternalBioscore->value => 0,
            Field::ExternalHotelbeds->value => 1,
            Field::InfoPropertyType->value => 7,
            Field::InfoAddress->value => 3,
            Field::InfoPostalCode->value => 13,
            Field::InfoLongitude->value => 6,
            Field::InfoLatitude->value => 5,
            Field::InfoRooms->value => 4,
            Field::InfoDestinationId->value => 8,
            Field::InfoDestinationName->value => 9,
            Field::InfoCountryId->value => 10,
            Field::InfoCountryName->value => 11,
            Field::InfoCountryIso->value => 12,
            Field::InfoZone->value => 14,
            Field::InfoCategoryName->value => 15,
            Field::InfoBrandId->value => 16,
            Field::InfoBrandName->value => 17,
            Field::Name->value => 2
        ]);
    }

    public function getSource(): ExternalSource
    {
        return ExternalSource::Bioscore;
    }
}