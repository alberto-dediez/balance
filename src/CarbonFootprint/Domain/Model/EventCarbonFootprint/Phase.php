<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;

enum Phase: string
{
    case Materials   = 'materials';
    case Assembly       = 'assembly';
    case Celebration = 'celebration';
    case Breakdown    = 'breakdown';

    /** @return list<SectionDefinition> */
    public function sections(): array
    {
        return match ($this) {
            self::Materials => [
                new SectionDefinition('materials', Source::Materials, ActivityType::Material),
                new SectionDefinition('cateringItems', Source::Materials, ActivityType::CateringItem),
                new SectionDefinition('merchandisingItems', Source::Materials, ActivityType::MerchandisingItem),
            ],
            self::Assembly => [
                new SectionDefinition('combustibles', Source::FixedFacilities, ActivityType::Combustible),
                new SectionDefinition('roadTransports', Source::FreightTransport, ActivityType::RoadTransport),
                new SectionDefinition('machineryFuels', Source::MachineryOperation, ActivityType::MachineryFuel),
                new SectionDefinition('machineryElectricity', Source::MachineryOperation, ActivityType::Electricity),
                new SectionDefinition('buildingElectricity', Source::BuildingElectricity, ActivityType::Electricity),
                new SectionDefinition('overnightStays', Source::SuppliersAndStaffAccommodation, ActivityType::OvernightStay),
                new SectionDefinition('mobilities', Source::SuppliersAndStaffMobility, ActivityType::Mobility),
                new SectionDefinition('wastes', Source::WasteManagement, ActivityType::Waste),
            ],
            self::Celebration => [
                new SectionDefinition('combustibles', Source::FixedFacilities, ActivityType::Combustible),
                new SectionDefinition('roadTransports', Source::FreightTransport, ActivityType::RoadTransport),
                new SectionDefinition('machineryFuels', Source::MachineryOperation, ActivityType::MachineryFuel),
                new SectionDefinition('machineryElectricity', Source::MachineryOperation, ActivityType::Electricity),
                new SectionDefinition('buildingElectricity', Source::BuildingElectricity, ActivityType::Electricity),
                new SectionDefinition('overnightStays', Source::SuppliersAndStaffAccommodation, ActivityType::OvernightStay),
                new SectionDefinition('mobilities', Source::SuppliersAndStaffMobility, ActivityType::Mobility),
                new SectionDefinition('attendeesMobilities', Source::AttendeesMobility, ActivityType::Mobility),
                new SectionDefinition('attendeesOvernightStays', Source::AttendeesAccommodation, ActivityType::OvernightStay),
                new SectionDefinition('wastes', Source::WasteManagement, ActivityType::Waste),
            ],
            self::Breakdown => [
                new SectionDefinition('combustibles', Source::FixedFacilities, ActivityType::Combustible),
                new SectionDefinition('roadTransports', Source::FreightTransport, ActivityType::RoadTransport),
                new SectionDefinition('machineryFuels', Source::MachineryOperation, ActivityType::MachineryFuel),
                new SectionDefinition('machineryElectricity', Source::MachineryOperation, ActivityType::Electricity),
                new SectionDefinition('buildingElectricity', Source::BuildingElectricity, ActivityType::Electricity),
                new SectionDefinition('overnightStays', Source::SuppliersAndStaffAccommodation, ActivityType::OvernightStay),
                new SectionDefinition('mobilities', Source::SuppliersAndStaffMobility, ActivityType::Mobility),
                new SectionDefinition('wastes', Source::WasteManagement, ActivityType::Waste),
            ],
        };
    }

    /** @return list<SectionDefinition> */
    public function providerSections(): array
    {
        return match ($this) {
            self::Materials => [],
            self::Assembly, self::Celebration, self::Breakdown => [
                new SectionDefinition('combustibles', Source::FixedFacilities, ActivityType::Combustible),
                new SectionDefinition('machineryFuels', Source::MachineryOperation, ActivityType::MachineryFuel),
                new SectionDefinition('overnightStays', Source::SuppliersAndStaffAccommodation, ActivityType::OvernightStay),
                new SectionDefinition('mobilities', Source::SuppliersAndStaffMobility, ActivityType::Mobility),
            ],
        };
    }
}
