<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\ExternalSource\Property\Import;

use App\Property\Application\Property\Import\Field;
use App\Property\Application\Property\Import\FieldExtractor;
use App\Property\Domain\Model\Property\ExternalSource;

class ImportRestel implements ImportConfiguration
{
    public function getFieldExtractor(): FieldExtractor
    {
        return new FieldExtractor([
            Field::ExternalRestel->value => 0,
            Field::ExternalHotelbeds->value => 1,
            Field::InfoPropertyType->value => 4,
            Field::InfoAddress->value => 7,
            Field::InfoPostalCode->value => 8,
            Field::InfoLongitude->value => 12,
            Field::InfoLatitude->value => 13,
            Field::InfoRooms->value => 6,
            Field::InfoZone->value => 9,
            Field::InfoDestinationName->value => 10,
            Field::InfoCountryName->value => 11,
            Field::Name->value => 2,
        ]);
    }

    public function getSource(): ExternalSource
    {
        return ExternalSource::Restel;
    }
}
