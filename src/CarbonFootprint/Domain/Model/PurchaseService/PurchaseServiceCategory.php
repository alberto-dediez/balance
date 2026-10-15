<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PurchaseService;

enum PurchaseServiceCategory: string
{
    case ProfessionalServices = 'professional_services';
    case Marketing            = 'marketing';
    case Technology           = 'technology';
    case Logistics            = 'logistics';
    case Facilities           = 'facilities';
    case Others               = 'others';
}
