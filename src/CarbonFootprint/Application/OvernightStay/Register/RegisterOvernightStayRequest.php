<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OvernightStay\Register;

use App\CarbonFootprint\Domain\Model\OvernightStay\CategoryOvernight;
use App\Shared\Application\CommandBus\Command;

class RegisterOvernightStayRequest implements Command
{
    public function __construct(
        public string $name = '',
        public CategoryOvernight $category = CategoryOvernight::Attendees,
    ) {}
}
