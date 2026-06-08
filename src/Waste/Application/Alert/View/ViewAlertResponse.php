<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\View;

use App\Waste\Domain\Model\Alert\Alert;

class ViewAlertResponse
{
    public function __construct(
        public Alert $alert,
    ) {}
}
