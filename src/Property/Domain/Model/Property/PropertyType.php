<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Property;

enum PropertyType: string
{
    case Hotel = 'Hotel';

    case Aparthotel = 'Aparthotel';

    case VacationalHotel = 'VacationalHotel';

    case ResortHotel = 'ResortHotel';

    case BusinessHotel = 'BusinessHotel';

    case SpaHotel = 'SpaHotel';

    case RuralAccommodation = 'RuralAccommodation';

    case Camping = 'Camping';

    case CityHotel = 'CityHotel';

    case Flat = 'Flat';

    case Hostel = 'Hostel';

    case Apartment = 'Apartment';

    case Motel = 'Motel';

    case Other = 'Other';

    public static function translate(string $name): PropertyType
    {
        $propertyType = PropertyType::Hotel;

        $propertyType = match (strtolower($name)) {
            'albergue' => PropertyType::Hostel,
            'apartamentos' => PropertyType::Apartment,
            'aparthotel', 'apartahotel' => PropertyType::Aparthotel,
            'camping' => PropertyType::Camping,
            'hostal' => PropertyType::Hostel,
            'hotel' => PropertyType::Hotel,
            'motel' => PropertyType::Motel,
            'apartamento', 'residencia' => PropertyType::Flat,
            'resort', 'hotel resort', 'villa' => PropertyType::ResortHotel,
            'riad', 'pensión' => PropertyType::Other,
            'rural', 'alojamiento rural' => PropertyType::RuralAccommodation,
            'hotel vacacional' => PropertyType::VacationalHotel,
            'hotel business' => PropertyType::BusinessHotel,
            'hotel balneario' => PropertyType::SpaHotel,
            'hotel urbano' => PropertyType::CityHotel,
            default => $propertyType,
        };

        return $propertyType;
    }
}
