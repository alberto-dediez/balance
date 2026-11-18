<?php

declare(strict_types=1);

namespace App\Tests\Unit\Assistant\Application\ActionPlan;

use App\Assistant\Application\ActionPlan\ActionPlanAuthorizer;

class ActionPlanAuthorizerMother
{
    public static function create(): ActionPlanAuthorizer
    {
        return new ActionPlanAuthorizer();
    }
}
