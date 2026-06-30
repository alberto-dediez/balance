<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

enum Source: string
{
    case Materials                    = 'materials';
    case FixedFacilities              = 'fixed_facilities';
    case FreightTransport             = 'freight_transport';
    case MachineryOperation           = 'machinery_operation';
    case BuildingElectricity          = 'building_electricity';
    case SuppliersAndStaffAccommodation = 'suppliers_and_staff_accommodation';
    case AttendeesAccommodation       = 'attendees_accommodation';
    case SuppliersAndStaffMobility    = 'suppliers_and_staff_mobility';
    case AttendeesMobility            = 'attendees_mobility';
    case WasteManagement              = 'waste_management';
}
