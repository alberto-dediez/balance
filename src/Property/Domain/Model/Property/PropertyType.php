<?php

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

        switch(strtolower($name)) {
            case 'albergue':
                $propertyType = PropertyType::Hostel;
                break;
            case 'apartamentos':
                $propertyType = PropertyType::Apartment;
                break;
            case 'aparthotel':
            case 'apartahotel':
                $propertyType = PropertyType::Aparthotel;
                break;
            case 'camping':
                $propertyType = PropertyType::Camping;
                break;
            case 'hostal':
                $propertyType = PropertyType::Hostel;
                break;
            case 'hotel':
                $propertyType = PropertyType::Hotel;
                break;
            case 'motel':
                $propertyType = PropertyType::Motel;
                break;
            case 'apartamento':
            case 'residencia':
                $propertyType = PropertyType::Flat;
                break;
            case 'resort':
            case 'hotel resort':
            case 'villa':
                $propertyType = PropertyType::ResortHotel;
                break;
            case 'riad':
            case 'pensión':
                $propertyType = PropertyType::Other;
                break;
            case 'rural':
            case 'alojamiento rural':
                $propertyType = PropertyType::RuralAccommodation;
                break;
            case 'hotel vacacional':
                $propertyType = PropertyType::VacationalHotel;
                break;      
            case 'hotel business':
                $propertyType = PropertyType::BusinessHotel;
                break;      
            case 'hotel balneario':
                $propertyType = PropertyType::SpaHotel;
                break;      
            case 'hotel urbano':
                $propertyType = PropertyType::CityHotel;
                break;      
        }

        return $propertyType;;
    }
}