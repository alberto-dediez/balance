<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprint;

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
    case OwnVehicles                  = 'own_vehicles';
    case RefrigerantLeaks             = 'refrigerant_leaks';
    case PurchasedGoodsAndServices    = 'purchased_goods_and_services';
    case CapitalGoods                 = 'capital_goods';
    case FuelAndEnergyRelated         = 'fuel_and_energy_related';
    case UpstreamTransport            = 'upstream_transport';
    case OperationsWaste              = 'operations_waste';
    case BusinessTravel               = 'business_travel';
    case EmployeeCommuting            = 'employee_commuting';
    case DownstreamTransport          = 'downstream_transport';
    case SoldProductsProcessing       = 'sold_products_processing';
    case SoldProductsUse              = 'sold_products_use';
    case SoldProductsEndOfLife        = 'sold_products_end_of_life';
    case DownstreamLeasedAssets       = 'downstream_leased_assets';
    case Franchises                   = 'franchises';
    case Investments                  = 'investments';
}
