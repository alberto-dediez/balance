<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Dashboard;

use App\Shared\Domain\Model\Filter;

class GetAdminDashboardRequest
{
    use Filter;

    public function __construct(
        int $limit = 0,
    ) {
        $this->limit = $limit;
    }
}
