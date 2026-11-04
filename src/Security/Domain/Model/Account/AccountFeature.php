<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Account;

enum AccountFeature: string
{
    case AccountTripCalculator = 'account_trip_calculator';
    case PropertyTripCalculators = 'property_trip_calculators';
    case Events = 'events';
    case Compensation = 'compensation';
    case OrganizationalCarbonFootprint = 'organizational_carbon_footprint';
}
