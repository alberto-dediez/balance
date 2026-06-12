<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Incidence\Dashboard;

use App\FoodLoss\Domain\Model\Incidence\IncidenceFilter;

class GetIncidenceDashboardRequest extends IncidenceFilter
{
    public ?\DateTimeImmutable $dateFrom = null;

    public ?\DateTimeImmutable $dateTo = null;
}
