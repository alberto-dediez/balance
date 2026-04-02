<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\CriticalPoint\Update;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\CriticalPoint\PriorityAssessment;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateCriticalPointResponse implements CommandResponse
{
    /**
     * @param ServiceCategoryId[] $serviceCategoryIds
     */
    public function __construct(
        public CriticalPointId $criticalPointId,
        public string $name,
        public array $serviceCategoryIds = [],
        public PriorityAssessment $prevention = new PriorityAssessment(),
        public PriorityAssessment $humanDonation = new PriorityAssessment(),
        public PriorityAssessment $animalFeed = new PriorityAssessment(),
        public PriorityAssessment $transformation = new PriorityAssessment(),
        public PriorityAssessment $composting = new PriorityAssessment(),
    ) {}
}
