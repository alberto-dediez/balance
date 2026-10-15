<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

enum ActivityType: string
{
    case Material          = 'material';
    case CateringItem      = 'catering_item';
    case MerchandisingItem = 'merchandising_item';
    case PurchaseService = 'purchase_service';
    case CapitalGood = 'capital_good';
    case Combustible       = 'combustible';
    case Electricity       = 'electricity';
    case MachineryFuel     = 'machinery_fuel';
    case Mobility          = 'mobility';
    case OvernightStay     = 'overnight_stay';
    case RoadTransport     = 'road_transport';
    case Waste             = 'waste';
    case RefrigerantGas    = 'refrigerant_gas';

    public static function fromActivity(Activity $activity): self
    {
        return match (true) {
            $activity instanceof MaterialActivity          => self::Material,
            $activity instanceof CateringItemActivity      => self::CateringItem,
            $activity instanceof MerchandisingItemActivity => self::MerchandisingItem,
            $activity instanceof PurchaseServiceActivity => self::PurchaseService,
            $activity instanceof CapitalGoodActivity => self::CapitalGood,
            $activity instanceof CombustibleActivity       => self::Combustible,
            $activity instanceof ElectricityActivity       => self::Electricity,
            $activity instanceof MachineryFuelActivity     => self::MachineryFuel,
            $activity instanceof MobilityActivity          => self::Mobility,
            $activity instanceof OvernightStayActivity     => self::OvernightStay,
            $activity instanceof RoadTransportActivity     => self::RoadTransport,
            $activity instanceof WasteActivity             => self::Waste,
            $activity instanceof RefrigerantGasActivity    => self::RefrigerantGas,
            default => throw new \InvalidArgumentException(sprintf('Unknown activity class "%s".', $activity::class)),
        };
    }
}
