<?php

declare(strict_types=1);

namespace App\Property\Application\Property\Import;

enum Field: string
{
    case ExternalRestel = 'external_restel';

    case ExternalHotelbeds = 'external_hotelbeds';

    case ExternalBioscore = 'external_bioscore';

    case InfoPropertyType = 'info_property_type';

    case InfoBrandId = 'info_brand_id';

    case InfoBrandName = 'info_brand_name';

    case InfoCategoryId = 'info_category_id';

    case InfoCategoryName = 'info_category_name';

    case InfoAddress = 'info_address';

    case InfoPostalCode = 'info_postal_code';

    case InfoLongitude = 'info_longitude';

    case InfoLatitude = 'info_latitude';

    case InfoRooms = 'info_rooms';

    case InfoWeb = 'info_web';

    case InfoZone = 'info_zone';

    case InfoDestinationId = 'info_destination_id';

    case InfoDestinationName = 'info_destination_name';

    case InfoCountryId = 'info_country_id';

    case InfoCountryName = 'info_country_name';

    case InfoCountryIso = 'info_country_iso';

    case Name = 'name';
}
