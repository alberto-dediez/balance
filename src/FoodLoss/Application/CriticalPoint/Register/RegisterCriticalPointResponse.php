<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\CriticalPoint\Register;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Model\CriticalPoint\PriorityAssessment;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use App\Shared\Application\CommandBus\CommandResponse;

class RegisterCriticalPointResponse implements CommandResponse
{
    /**
     * @param ServiceCategoryId[] $serviceCategoryIds
     * @param SurplusCauseId[]    $surplusCauseIds
     */
    public function __construct(
        public CriticalPointId $criticalPointId,
        public string $name,
        public array $serviceCategoryIds = [],
        public array $surplusCauseIds = [],
        public PriorityAssessment $prevention = new PriorityAssessment(),
        public PriorityAssessment $humanDonation = new PriorityAssessment(),
        public PriorityAssessment $animalFeed = new PriorityAssessment(),
        public PriorityAssessment $transformation = new PriorityAssessment(),
        public PriorityAssessment $composting = new PriorityAssessment(),
    ) {}
}
