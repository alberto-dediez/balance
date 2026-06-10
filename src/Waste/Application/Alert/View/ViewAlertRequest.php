<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\View;

use App\Waste\Domain\Model\Alert\AlertId;

class ViewAlertRequest
{
    public function __construct(
        public AlertId $alertId,
    ) {}
}
