<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\RequestSignature;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanId;
use App\Shared\Application\CommandBus\Command;

class RequestSignaturePreventionPlanRequest implements Command
{
    public function __construct(
        public PreventionPlanId $preventionPlanId,
        public int $versionNumber,
        public string $pdfPath,
    ) {}
}
