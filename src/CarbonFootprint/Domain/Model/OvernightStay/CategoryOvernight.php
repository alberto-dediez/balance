<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OvernightStay;

enum CategoryOvernight: string
{
    case Attendees = 'attendees';
    case Suppliers = 'suppliers';
    case OrganizingTeam = 'organizing_team';
}
